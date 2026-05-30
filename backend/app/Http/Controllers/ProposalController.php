<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalRequest;
use App\Http\Resources\ContractResource;
use App\Models\Proposal;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $proposals = $request->user()->proposals()->with(['client', 'items', 'company'])->get();
        return response()->json($proposals);
    }

    public function store(StoreProposalRequest $request)
    {
        $validated = $request->validated();

        $proposal = DB::transaction(function () use ($request, $validated) {
            $company = $request->user()->company;
            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            // Assuming standard 0% tax for now, could be dynamic
            $tax = 0;
            $total = $subtotal + $tax;

            $proposal = $request->user()->proposals()->create([
                'company_id' => $company?->id,
                'client_id' => $validated['client_id'],
                'title' => $validated['title'],
                'status' => 'Draft',
                'issue_date' => $validated['issue_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($validated['items'] as $item) {
                $proposal->items()->create([
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $proposal->load('items', 'client', 'company');
        });

        return response()->json($proposal, 201);
    }

    public function show(Request $request, Proposal $proposal)
    {
        $this->ensureOwnedByUser($request, $proposal);

        return response()->json($proposal->load(['client', 'items', 'company']));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $this->ensureOwnedByUser($request, $proposal);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'status' => ['sometimes', 'required', Rule::in(['Draft', 'Sent', 'Approved', 'Rejected', 'Expired'])],
            'issue_date' => 'sometimes|required|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $proposal->update([
            'title' => array_key_exists('title', $validated) ? InputSanitizer::text($validated['title']) : $proposal->title,
            'status' => $validated['status'] ?? $proposal->status,
            'issue_date' => $validated['issue_date'] ?? $proposal->issue_date,
            'expiry_date' => array_key_exists('expiry_date', $validated) ? $validated['expiry_date'] : $proposal->expiry_date,
            'notes' => array_key_exists('notes', $validated) ? InputSanitizer::multilineText($validated['notes']) : $proposal->notes,
        ]);

        return response()->json($proposal->load(['client', 'items', 'company']));
    }

    public function destroy(Request $request, Proposal $proposal)
    {
        $this->ensureOwnedByUser($request, $proposal);

        $proposal->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function convertToContract(Request $request, Proposal $proposal)
    {
        $this->ensureOwnedByUser($request, $proposal);

        $contract = $proposal->user->contracts()->create([
            'company_id' => $proposal->company_id ?? $proposal->user->company?->id,
            'client_id' => $proposal->client_id,
            'proposal_id' => $proposal->id,
            'title' => 'Contract for ' . $proposal->title,
            'status' => 'Draft',
        ]);

        return (new ContractResource($contract->load(['client', 'proposal', 'company'])))
            ->response()
            ->setStatusCode(201);
    }

    public function generatePdf(Request $request, Proposal $proposal)
    {
        $this->ensureOwnedByUser($request, $proposal);

        $proposal->load(['client', 'items', 'user', 'company']);
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.proposal', compact('proposal'));
        return $pdf->download('proposal-' . $proposal->id . '.pdf');
    }
}
