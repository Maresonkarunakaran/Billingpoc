<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
            'business_name' => trim((string) $this->input('business_name')),
            'branch_name' => trim((string) $this->input('branch_name')),
            'gstin' => strtoupper(trim((string) $this->input('gstin'))),
            'city' => trim((string) $this->input('city')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'business_name' => 'required|string|max:150',
            'branch_name' => 'nullable|string|max:150',
            'branch_type' => 'required|in:shop,warehouse',
            'state_code' => 'required|string|size:2',
            'city' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:15',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email address already exists. Please sign in instead.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'Password must be at least 6 characters.',
            'state_code.required' => 'Please select the state for GST compliance.',
        ];
    }
}
