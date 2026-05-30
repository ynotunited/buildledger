<?php

namespace App\Http\Requests;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'              => [
                'required',
                Rule::exists('clients', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'title'                  => 'required|string|max:255',
            'issue_date'             => 'required|date',
            'expiry_date'            => 'nullable|date|after_or_equal:issue_date',
            'notes'                  => 'nullable|string|max:5000',
            'items'                  => 'required|array|min:1',
            'items.*.name'           => 'required|string|max:255',
            'items.*.description'    => 'nullable|string|max:1000',
            'items.*.quantity'       => 'required|integer|min:1|max:10000',
            'items.*.unit_price'     => 'required|numeric|min:0|max:99999999',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(fn ($item) => [
                'name' => InputSanitizer::text($item['name'] ?? null),
                'description' => InputSanitizer::multilineText($item['description'] ?? null),
                'quantity' => $item['quantity'] ?? null,
                'unit_price' => $item['unit_price'] ?? null,
            ])
            ->all();

        $this->merge([
            'title' => InputSanitizer::text($this->input('title')),
            'notes' => InputSanitizer::multilineText($this->input('notes')),
            'items' => $items,
        ]);
    }
}
