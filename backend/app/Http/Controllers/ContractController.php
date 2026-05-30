<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Resources\PublicContractResource;
use App\Models\Contract;
use App\Support\InputSanitizer;
use App\Notifications\ContractSigned;
use App\Notifications\ProposalApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = $request->user()->contracts()->with(['client', 'proposal', 'company'])->get();
        return ContractResource::collection($contracts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'proposal_id' => [
                'nullable',
                Rule::exists('proposals', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'title' => 'required|string|max:255',
            'body_content' => 'nullable|string|max:50000',
        ]);

        if (isset($validated['proposal_id'])) {
            $proposal = $request->user()->proposals()->findOrFail($validated['proposal_id']);

            if ((int) $proposal->client_id !== (int) $validated['client_id']) {
                throw ValidationException::withMessages([
                    'proposal_id' => 'The selected proposal does not belong to the selected client.',
                ]);
            }
        }

        $contract = $request->user()->contracts()->create([
            'company_id' => $request->user()->company?->id,
            'client_id' => $validated['client_id'],
            'proposal_id' => $validated['proposal_id'] ?? null,
            'title' => InputSanitizer::text($validated['title']),
            'body_content' => InputSanitizer::richText($validated['body_content'] ?? null),
            'status' => 'Draft',
        ]);

        return (new ContractResource($contract->load(['client', 'proposal', 'company'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Contract $contract)
    {
        $this->ensureOwnedByUser($request, $contract);

        return new ContractResource($contract->load(['client', 'proposal', 'company']));
    }

    public function update(Request $request, Contract $contract)
    {
        $this->ensureOwnedByUser($request, $contract);
        $previousStatus = $contract->status;

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body_content' => 'nullable|string|max:50000',
            'status' => ['sometimes', 'required', Rule::in(['Draft', 'Sent', 'Signed'])],
        ]);

        $requestedStatus = $validated['status'] ?? null;
        $nextStatus = $validated['status'] ?? $contract->status;

        if ($nextStatus === 'Signed' && $contract->status !== 'Signed') {
            throw ValidationException::withMessages([
                'status' => 'Signed status can only be set through the public signing flow.',
            ]);
        }

        $contract->update([
            'title' => array_key_exists('title', $validated) ? InputSanitizer::text($validated['title']) : $contract->title,
            'body_content' => array_key_exists('body_content', $validated)
                ? InputSanitizer::richText($validated['body_content'])
                : $contract->body_content,
            'status' => $nextStatus,
        ]);

        if ($requestedStatus === 'Sent' && ($previousStatus !== 'Sent' || ! $contract->hasActiveSigningLink())) {
            $this->issueSigningLink($contract);
        } elseif ($requestedStatus !== null && $nextStatus !== 'Signed') {
            $this->revokeSigningLink($contract);
        }

        return new ContractResource($contract->load(['client', 'proposal', 'company']));
    }

    public function destroy(Request $request, Contract $contract)
    {
        $this->ensureOwnedByUser($request, $contract);

        $contract->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function convertToInvoice(Request $request, Contract $contract)
    {
        $this->ensureOwnedByUser($request, $contract);

        $proposal = $contract->proposal;

        $invoice = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $contract, $proposal) {
            $lastInvoice = \App\Models\Invoice::where('user_id', $request->user()->id)->latest('id')->first();
            $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1;
            $invoiceNumber = 'INV-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $invoice = $request->user()->invoices()->create([
                'company_id' => $contract->company_id ?? $request->user()->company?->id,
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'Draft',
                'issue_date' => now(),
                'due_date' => now()->addDays(14),
                'subtotal' => $proposal ? $proposal->subtotal : 0,
                'tax' => $proposal ? $proposal->tax : 0,
                'total' => $proposal ? $proposal->total : 0,
            ]);

            if ($proposal) {
                foreach ($proposal->items as $item) {
                    $invoice->items()->create([
                        'name' => $item->name,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total' => $item->total,
                    ]);
                }
            }

            return $invoice->load('items', 'client', 'contract', 'company');
        });

        return response()->json($invoice, 201);
    }

    public function getPublicContract($uuid)
    {
        $contract = Contract::where('signing_token', $uuid)->with(['client', 'company'])->firstOrFail();

        if (! $contract->hasActiveSigningLink()) {
            return response()->json(['message' => 'This signing link is invalid or expired.'], 410);
        }

        return response()->json((new PublicContractResource($contract))->resolve());
    }

    public function signPublicContract(Request $request, $uuid)
    {
        $contract = Contract::where('signing_token', $uuid)->firstOrFail();

        if ($contract->status !== 'Sent') {
            return response()->json(['message' => 'This contract is not available for signing.'], 409);
        }

        if (! $contract->hasActiveSigningLink()) {
            return response()->json(['message' => 'This signing link is invalid or expired.'], 410);
        }

        if ($contract->status === 'Signed') {
            return response()->json(['message' => 'Contract already signed'], 400);
        }

        $validated = $request->validate([
            'signature_name' => 'required|string|max:255',
        ]);

        $contract->update([
            'status' => 'Signed',
            'client_signature_name' => InputSanitizer::text($validated['signature_name']),
            'client_signature_ip' => $request->ip(),
            'client_signed_at' => now(),
            'signing_token' => null,
            'signing_token_expires_at' => null,
        ]);

        // Automatically update the linked proposal status if exists
        if ($contract->proposal) {
            $contract->proposal->update(['status' => 'Approved']);
            // Notify the proposal owner
            $contract->user->notify(new ProposalApproved($contract->proposal));
        }

        // Notify the contract owner
        $contract->user->notify(new ContractSigned($contract));

        return response()->json([
            'message' => 'Contract signed successfully',
            'contract' => (new ContractResource($contract->load(['client', 'proposal', 'company'])))->resolve(),
        ]);
    }

    private function issueSigningLink(Contract $contract): void
    {
        $contract->forceFill([
            'status' => 'Sent',
            'sent_at' => $contract->sent_at ?? now(),
            'signing_token' => (string) Str::uuid(),
            'signing_token_expires_at' => Carbon::now()->addHours(
                (int) config('security.contract_signing_link_ttl_hours', 168)
            ),
        ])->save();
    }

    private function revokeSigningLink(Contract $contract): void
    {
        $contract->forceFill([
            'signing_token' => null,
            'signing_token_expires_at' => null,
            'sent_at' => $contract->status === 'Signed' ? $contract->sent_at : null,
        ])->save();
    }
}
