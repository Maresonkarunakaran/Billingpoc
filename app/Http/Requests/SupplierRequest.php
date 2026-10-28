<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|max:10',
            'state_code' => 'required|string|size:2',
            'state_name' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'opening_balance' => 'nullable|numeric',
        ];
    }
}
