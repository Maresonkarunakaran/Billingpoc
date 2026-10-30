@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-plus-circle text-primary mr-2"></i>Add Product to Catalog</h4>
        <span class="text-muted text-sm">Configure identification, GST rates, unit definitions, and wholesale/retail price tiers.</span>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Products</a>
</div>

<form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Basic Information</div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="font-weight-bold text-sm">Product Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="E.g. Fortune Sunlite Sunflower Oil 1L">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">SKU (Unique Code) *</label>
                    <input type="text" name="sku" id="sku" class="form-control" value="{{ old('sku') }}" required placeholder="E.g. OIL-FOR-1L">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Barcode / EAN (Optional)</label>
                    <div class="input-group">
                        <input type="text" name="barcode" id="barcode" class="form-control font-weight-bold" value="{{ old('barcode') }}" placeholder="Scan or type barcode">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary" id="btn-generate-barcode" title="Click to auto-generate unique barcode">
                                <i class="fas fa-barcode mr-1"></i> Generate
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Scan with USB barcode scanner or click <strong>Generate</strong>.</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">-- None / General --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Base Unit of Measurement *</label>
                    <select name="base_unit_id" class="form-control font-weight-bold" required>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('base_unit_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Low-Stock Alert Level</label>
                    <input type="number" name="alert_quantity" class="form-control" value="{{ old('alert_quantity', 10) }}" min="0" step="any">
                    <small class="form-text text-muted">This sets a low-stock warning only; it does not add stock.</small>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="text-sm font-weight-bold">Product Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Specifications, pack details, supplier references...">{{ old('description') }}</textarea>
            </div>
            <div class="form-group mt-3 mb-0">
                <label class="text-sm font-weight-bold" for="image">Product Image (Optional)</label>
                <input type="file" id="image" name="image" class="form-control-file d-block" accept=".jpg,.jpeg,.png,.webp">
                <small class="form-text text-muted">JPG, PNG, or WebP; maximum 2 MB. Images appear on POS product cards.</small>
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
                    <input type="number" name="purchase_price" class="form-control font-weight-bold" value="{{ old('purchase_price', '0.00') }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-primary">Retail Selling Price (₹) *</label>
                    <input type="number" name="retail_price" class="form-control font-weight-bold text-primary" value="{{ old('retail_price', '0.00') }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-success">Wholesale Selling Price (₹) *</label>
                    <input type="number" name="wholesale_price" class="form-control font-weight-bold text-success" value="{{ old('wholesale_price', '0.00') }}" required min="0" step="0.01">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">Min Wholesale Quantity</label>
                    <input type="number" name="min_wholesale_qty" class="form-control font-weight-bold" value="{{ old('min_wholesale_qty', 10) }}" min="1" step="1">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">GST Tax Rate *</label>
                    <select name="gst_rate" class="form-control font-weight-bold" required>
                        <option value="0" {{ old('gst_rate') == 0 ? 'selected' : '' }}>0% (Exempt)</option>
                        <option value="5" {{ old('gst_rate') == 5 ? 'selected' : '' }}>5% (CGST 2.5% + SGST 2.5% / IGST 5%)</option>
                        <option value="12" {{ old('gst_rate') == 12 ? 'selected' : '' }}>12% (CGST 6% + SGST 6% / IGST 12%)</option>
                        <option value="18" {{ old('gst_rate', 18) == 18 ? 'selected' : '' }}>18% (CGST 9% + SGST 9% / IGST 18%)</option>
                        <option value="28" {{ old('gst_rate') == 28 ? 'selected' : '' }}>28% (CGST 14% + SGST 14% / IGST 28%)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">Tax Treatment Type *</label>
                    <select name="tax_type" class="form-control font-weight-bold" required>
                        <option value="exclusive" {{ old('tax_type', 'exclusive') === 'exclusive' ? 'selected' : '' }}>Tax Exclusive (Price + GST calculated on top)</option>
                        <option value="inclusive" {{ old('tax_type') === 'inclusive' ? 'selected' : '' }}>Tax Inclusive (MRP includes all applicable GST)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Initial Opening Stock -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Initial Opening Stock (Optional)</div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">Branch for Opening Stock</label>
                    <select name="opening_stock_branch_id" class="form-control">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ session('active_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ strtoupper($b->type) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold text-sm">Opening Quantity</label>
                    <input type="number" name="opening_stock" class="form-control" value="{{ old('opening_stock', 0) }}" min="0" step="1" placeholder="Whole number quantity on hand">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm mb-4">
        <i class="fas fa-save mr-1"></i> Save Product to Catalog
    </button>
</form>

@push('scripts')
<script>
    document.getElementById('btn-generate-barcode')?.addEventListener('click', function() {
        // Generate a 12-digit standard barcode starting with 890 (India) or 12 random digits
        const prefix = '890';
        const randomPart = Math.floor(100000000 + Math.random() * 900000000).toString();
        const fullBarcode = prefix + randomPart;
        const input = document.getElementById('barcode');
        input.value = fullBarcode;
        input.classList.add('is-valid');
        setTimeout(() => input.classList.remove('is-valid'), 1200);
    });
</script>
@endpush
@endsection
