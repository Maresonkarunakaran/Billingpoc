@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-edit text-primary mr-2"></i>Edit Product: {{ $product->name }}</h4>
        <span class="text-muted text-sm">Update prices, GST taxation and branch-specific price tiers.</span>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Products</a>
</div>

<form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Basic Information</div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="font-weight-bold text-sm">Product Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">SKU *</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- None / General --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id', $product->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Base Unit *</label>
                    <select name="base_unit_id" class="form-control font-weight-bold" required>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('base_unit_id', $product->base_unit_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Alert Quantity</label>
                    <input type="number" name="alert_quantity" class="form-control" value="{{ old('alert_quantity', $product->alert_quantity) }}" min="0" step="any">
                    <small class="form-text text-muted">This sets a low-stock warning only; it does not add stock.</small>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="text-sm font-weight-bold">Product Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
            </div>
            <div class="form-group mt-3 mb-0">
                <label class="text-sm font-weight-bold" for="image">Product Image</label>
                @if($imageUrl)
                    <div class="mb-2">
                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" style="width: 120px; height: 100px; object-fit: contain;" class="border rounded p-1">
                    </div>
                @endif
                <input type="file" id="image" name="image" class="form-control-file d-block" accept=".jpg,.jpeg,.png,.webp">
                <small class="form-text text-muted">Choose a new JPG, PNG, or WebP image (maximum 2 MB) to replace the current one.</small>
                @error('image')
                    <span class="text-danger text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <!-- Pricing & GST Details -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Pricing & GST Tax Setup</div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">Base Purchase Cost (₹) *</label>
                    <input type="number" name="purchase_price" class="form-control font-weight-bold" value="{{ old('purchase_price', $product->purchase_price) }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-primary">Retail Selling Price (₹) *</label>
                    <input type="number" name="retail_price" class="form-control font-weight-bold text-primary" value="{{ old('retail_price', $product->retail_price) }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-success">Wholesale Selling Price (₹) *</label>
                    <input type="number" name="wholesale_price" class="form-control font-weight-bold text-success" value="{{ old('wholesale_price', $product->wholesale_price) }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">Min Wholesale Quantity</label>
                    <input type="number" name="min_wholesale_qty" class="form-control font-weight-bold" value="{{ old('min_wholesale_qty', $product->min_wholesale_qty) }}" min="1" step="1">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">GST Tax Rate *</label>
                    <select name="gst_rate" class="form-control font-weight-bold" required>
                        <option value="0" {{ old('gst_rate', $product->gst_rate) == 0 ? 'selected' : '' }}>0% (Exempt)</option>
                        <option value="5" {{ old('gst_rate', $product->gst_rate) == 5 ? 'selected' : '' }}>5% (CGST 2.5% + SGST 2.5% / IGST 5%)</option>
                        <option value="12" {{ old('gst_rate', $product->gst_rate) == 12 ? 'selected' : '' }}>12% (CGST 6% + SGST 6% / IGST 12%)</option>
                        <option value="18" {{ old('gst_rate', $product->gst_rate) == 18 ? 'selected' : '' }}>18% (CGST 9% + SGST 9% / IGST 18%)</option>
                        <option value="28" {{ old('gst_rate', $product->gst_rate) == 28 ? 'selected' : '' }}>28% (CGST 14% + SGST 14% / IGST 28%)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">Tax Treatment Type *</label>
                    <select name="tax_type" class="form-control font-weight-bold" required>
                        <option value="exclusive" {{ old('tax_type', $product->tax_type) === 'exclusive' ? 'selected' : '' }}>Tax Exclusive</option>
                        <option value="inclusive" {{ old('tax_type', $product->tax_type) === 'inclusive' ? 'selected' : '' }}>Tax Inclusive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Branch Custom Overrides -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Branch Specific Price Overrides (Optional)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light text-xs text-uppercase text-muted">
                        <tr>
                            <th>Branch</th>
                            <th>Custom Retail Price (₹)</th>
                            <th>Custom Wholesale Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $b)
                            @php
                                $setting = $product->branchSettings->firstWhere('branch_id', $b->id);
                            @endphp
                            <tr>
                                <td class="font-weight-bold align-middle">{{ $b->name }} ({{ strtoupper($b->type) }})</td>
                                <td>
                                    <input type="number" name="branch_settings[{{ $b->id }}][retail_price]" class="form-control form-control-sm" 
                                           value="{{ $setting ? $setting->custom_retail_price : '' }}" placeholder="Default: ₹{{ $product->retail_price }}" step="0.01">
                                </td>
                                <td>
                                    <input type="number" name="branch_settings[{{ $b->id }}][wholesale_price]" class="form-control form-control-sm" 
                                           value="{{ $setting ? $setting->custom_wholesale_price : '' }}" placeholder="Default: ₹{{ $product->wholesale_price }}" step="0.01">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm mb-4">
        <i class="fas fa-save mr-1"></i> Update Product Details
    </button>
</form>
@endsection
