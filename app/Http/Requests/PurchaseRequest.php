<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'status' => 'required|in:ordered,received',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.gst_rate' => 'required|numeric|min:0|max:28',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,upi,card,cheque',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.quantity.integer' => 'Purchase quantity must be a whole number.',
        ];
    }
}
