<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_distributor')) {
            $this->merge([
                'is_distributor' => $this->boolean('is_distributor'),
            ]);
        }

        if (empty($this->name) && ! empty($this->shop_name)) {
            $this->merge(['name' => $this->shop_name]);
        }

        if ($this->has('gstin') && is_string($this->gstin)) {
            $cleaned = strtoupper(trim($this->gstin));
            $this->merge(['gstin' => $cleaned !== '' ? $cleaned : null]);
        }

        if ($this->has('gst_rate')) {
            $rawRate = strtolower(trim((string) $this->gst_rate));
            if ($rawRate === 'exempted' || $rawRate === 'exempt' || $rawRate === 'none') {
                $this->merge(['gst_rate' => 0]);
            } elseif ($rawRate === '') {
                $this->merge(['gst_rate' => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'shop_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|max:10',
            'state_code' => 'required|string|size:2',
            'state_name' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'is_distributor' => 'nullable|boolean',
            'gst_rate' => 'nullable|numeric|in:0,5,12,18,28',
        ];
    }

    public function messages(): array
    {
        return [
            'gstin.size' => 'The GST number must be exactly 15 characters (e.g. 33AAAAA0000A1Z5).',
            'gst_rate.in' => 'GST rate must be Exempted, 0%, 5%, 12%, 18%, or 28%.',
        ];
    }
}
