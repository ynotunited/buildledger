<?php

namespace App\Http\Requests;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'   => [
                'required',
                Rule::exists('clients', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'contract_id' => [
                'nullable',
                Rule::exists('contracts', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->id)
                ),
            ],
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status'      => 'nullable|in:Planning,Active,On Hold,Completed,Cancelled',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'budget'      => 'nullable|numeric|min:0|max:99999999',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => InputSanitizer::text($this->input('title')),
            'description' => InputSanitizer::multilineText($this->input('description')),
            'status' => InputSanitizer::text($this->input('status')),
        ]);
    }
}
