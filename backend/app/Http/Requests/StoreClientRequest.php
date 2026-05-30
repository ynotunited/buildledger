<?php

namespace App\Http\Requests;

use App\Support\InputSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth handled by sanctum middleware
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\-\s]{7,50}$/'],
            'company' => 'nullable|string|max:255',
            'status'  => ['nullable', Rule::in(['Lead', 'Negotiation', 'Active', 'Completed', 'Dormant'])],
            'address' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        if ($this->has('name')) {
            $sanitized['name'] = InputSanitizer::text($this->input('name'));
        }

        if ($this->has('email')) {
            $sanitized['email'] = $this->filled('email')
                ? mb_strtolower(trim((string) $this->input('email')))
                : null;
        }

        if ($this->has('phone')) {
            $sanitized['phone'] = InputSanitizer::text($this->input('phone'));
        }

        if ($this->has('company')) {
            $sanitized['company'] = InputSanitizer::text($this->input('company'));
        }

        if ($this->has('status')) {
            $sanitized['status'] = InputSanitizer::text($this->input('status'));
        }

        if ($this->has('address')) {
            $sanitized['address'] = InputSanitizer::multilineText($this->input('address'));
        }

        $this->merge($sanitized);
    }
}
