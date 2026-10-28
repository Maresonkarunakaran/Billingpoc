<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'pricing_type' => 'required|in:retail,wholesale',
            'idempotency_key' => 'nullable|string|max:64',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|in:cash,card,upi,bank_transfer,cheque,credit',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference_no' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one product item is required in the cart.',
            'items.*.quantity.min' => 'Item quantity must be greater than zero.',
            'items.*.quantity.integer' => 'Item quantity must be a whole number.',
        ];
    }
}
