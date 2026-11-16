<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gst_rate')) {
            $rawRate = strtolower(trim((string) $this->gst_rate));
            if ($rawRate === '' || $rawRate === 'exempted' || $rawRate === 'exempt' || $rawRate === 'none') {
                $this->merge(['gst_rate' => 0]);
            }
        } else {
            $this->merge(['gst_rate' => 0]);
        }

        if (! $this->has('tax_type') || empty($this->tax_type)) {
            $this->merge(['tax_type' => 'exclusive']);
        }
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : $product;
        $businessId = $this->user()->business_id;

        return [
            'name' => 'required|string|max:255',
            'sku' => "required|string|max:50|unique:products,sku,{$productId},id,business_id,{$businessId}",
            'barcode' => 'nullable|string|max:100',
            'hsn_code' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:categories,id',
            'base_unit_id' => 'required|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0',
            'min_wholesale_qty' => 'nullable|numeric|min:0.01',
            'gst_rate' => 'nullable|numeric|in:0,5,12,18,28',
            'tax_type' => 'nullable|in:exclusive,inclusive',
            'alert_quantity' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'dimensions:max_width=4000,max_height=4000', 'max:2048'],
            'opening_stock' => 'nullable|integer|min:0',
            'opening_stock_branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'opening_stock.integer' => 'Opening stock must be a whole number.',
        ];
    }
}
