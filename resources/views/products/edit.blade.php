@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-edit text-primary mr-2"></i>Edit Product: {{ $product->name }}</h4>
        <span class="text-muted text-sm">Update prices, GST taxation and branch-specific price tiers.</span>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Products</a>
</div>

<form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" id="productForm" data-prevent-enter-submit="true">
    @csrf
    @method('PUT')

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm">Basic Information</div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="font-weight-bold text-sm">Product Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">SKU *</label>
                    <input type="text" name="sku" id="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-sm">Barcode / EAN (Optional)</label>
                    <div class="input-group">
                        <input type="text" name="barcode" id="barcode" class="form-control font-weight-bold" value="{{ old('barcode', $product->barcode) }}" placeholder="Scan or type barcode">
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
        </div>
    </div>

    <!-- Pricing & GST Details -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-2 font-weight-bold text-sm d-flex justify-content-between align-items-center flex-wrap">
            <span><i class="fas fa-tags text-primary mr-2"></i>Pricing & GST Tax Setup</span>
            <span class="badge badge-primary px-2 py-1"><i class="fas fa-calculator mr-1"></i>Set Price Amount Option</span>
        </div>
        <div class="card-body p-4">
            <!-- Smart Price Setter / Markup Helper -->
            <div class="bg-light p-3 rounded border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <div>
                        <strong class="text-dark text-sm"><i class="fas fa-sliders-h text-primary mr-1"></i>Set Price Amount: Base Price, Wholesale Price & Retail Price</strong>
                        <span class="text-muted text-xs d-block">Base purchase price may increase or decrease anytime. Use quick markup presets or enter prices directly.</span>
                    </div>
                    <div class="custom-control custom-switch mt-1 mt-md-0">
                        <input type="checkbox" class="custom-control-input" id="editAutoRecalcSwitch" checked>
                        <label class="custom-control-label text-xs font-weight-bold text-primary" for="editAutoRecalcSwitch">Auto-calculate selling prices on cost change</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <label class="text-xs font-weight-bold text-primary mb-1">Retail Selling Markup Presets</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-primary btn-edit-retail-markup" data-pct="10">+10%</button>
                            <button type="button" class="btn btn-outline-primary btn-edit-retail-markup" data-pct="15">+15%</button>
                            <button type="button" class="btn btn-outline-primary btn-edit-retail-markup" data-pct="20">+20%</button>
                            <button type="button" class="btn btn-outline-primary btn-edit-retail-markup" data-pct="25">+25%</button>
                            <button type="button" class="btn btn-outline-primary btn-edit-retail-markup" data-pct="30">+30%</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-xs font-weight-bold text-success mb-1">Wholesale Selling Markup Presets</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-success btn-edit-wholesale-markup" data-pct="5">+5%</button>
                            <button type="button" class="btn btn-outline-success btn-edit-wholesale-markup" data-pct="8">+8%</button>
                            <button type="button" class="btn btn-outline-success btn-edit-wholesale-markup" data-pct="10">+10%</button>
                            <button type="button" class="btn btn-outline-success btn-edit-wholesale-markup" data-pct="12">+12%</button>
                            <button type="button" class="btn btn-outline-success btn-edit-wholesale-markup" data-pct="15">+15%</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">Base Purchase Cost (₹) *</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold">₹</span></div>
                        <input type="number" name="purchase_price" id="editPurchasePrice" class="form-control font-weight-bold" value="{{ old('purchase_price', $product->purchase_price) }}" required min="0" step="0.01">
                    </div>
                    <small class="form-text text-muted">Base cost from supplier / vendor.</small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-primary">Retail Selling Price (₹) *</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold text-primary">₹</span></div>
                        <input type="number" name="retail_price" id="editRetailPrice" class="form-control font-weight-bold text-primary" value="{{ old('retail_price', $product->retail_price) }}" required min="0" step="0.01">
                    </div>
                    <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                        <span class="text-muted">Profit: ₹ <span id="editRetailProfit">0.00</span></span>
                        <span class="text-primary badge badge-light border" id="editRetailMargin">0.00% margin</span>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm text-success">Wholesale Selling Price (₹) *</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold text-success">₹</span></div>
                        <input type="number" name="wholesale_price" id="editWholesalePrice" class="form-control font-weight-bold text-success" value="{{ old('wholesale_price', $product->wholesale_price) }}" required min="0" step="0.01">
                    </div>
                    <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                        <span class="text-muted">Profit: ₹ <span id="editWholesaleProfit">0.00</span></span>
                        <span class="text-success badge badge-light border" id="editWholesaleMargin">0.00% margin</span>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-sm">Min Wholesale Quantity</label>
                    <input type="number" name="min_wholesale_qty" id="min_wholesale_qty" class="form-control font-weight-bold" value="{{ old('min_wholesale_qty', number_format((float)$product->min_wholesale_qty, 2, '.', '')) }}" min="0.01" step="0.01" placeholder="10.00">
                    <small class="form-text text-muted">Wholesale bulk tier threshold.</small>
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <button type="submit" id="btnUpdateProduct" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm">
            <i class="fas fa-save mr-1"></i> Update Product Details
        </button>
        <button type="button" class="btn btn-outline-danger font-weight-bold px-3 py-2 shadow-sm" onclick="if(confirm('Are you sure you want to delete product \'{{ addslashes($product->name) }}\'? This action cannot be undone.')) { document.getElementById('delete-product-form-{{ $product->id }}').submit(); }">
            <i class="fas fa-trash-alt mr-1"></i> Delete Product
        </button>
    </div>
</form>

<form id="delete-product-form-{{ $product->id }}" action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    document.getElementById('btn-generate-barcode')?.addEventListener('click', function() {
        const prefix = '890';
        const randomPart = Math.floor(100000000 + Math.random() * 900000000).toString();
        const fullBarcode = prefix + randomPart;
        const input = document.getElementById('barcode');
        input.value = fullBarcode;
        input.classList.add('is-valid');
        setTimeout(() => input.classList.remove('is-valid'), 1200);
    });

    // Guard: Pressing enter in inputs will NOT prematurely submit the form
    // Updating the product only completes when explicitly clicking 'Update Product Details'
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
                e.preventDefault();
                const focusables = Array.from(productForm.querySelectorAll('input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'));
                const idx = focusables.indexOf(e.target);
                if (idx > -1 && idx + 1 < focusables.length) {
                    focusables[idx + 1].focus();
                }
                return false;
            }
        });
    }
    // Dynamic Pricing Engine & Margin Calculator for Edit Product
    (function() {
        const purchaseInput = document.getElementById('editPurchasePrice');
        const retailInput = document.getElementById('editRetailPrice');
        const wholesaleInput = document.getElementById('editWholesalePrice');
        const autoRecalcSwitch = document.getElementById('editAutoRecalcSwitch');

        let retailMarkupPct = 20;
        let wholesaleMarkupPct = 10;

        function updateMarginBadges() {
            const cost = parseFloat(purchaseInput?.value) || 0;
            const retail = parseFloat(retailInput?.value) || 0;
            const wholesale = parseFloat(wholesaleInput?.value) || 0;

            const retailProfit = retail - cost;
            const retailMargin = retail > 0 ? ((retailProfit / retail) * 100) : 0;
            const wholesaleProfit = wholesale - cost;
            const wholesaleMargin = wholesale > 0 ? ((wholesaleProfit / wholesale) * 100) : 0;

            const rProfitEl = document.getElementById('editRetailProfit');
            const rMarginEl = document.getElementById('editRetailMargin');
            const wProfitEl = document.getElementById('editWholesaleProfit');
            const wMarginEl = document.getElementById('editWholesaleMargin');

            if (rProfitEl) rProfitEl.textContent = retailProfit.toFixed(2);
            if (rMarginEl) rMarginEl.textContent = retailMargin.toFixed(2) + '% margin';
            if (wProfitEl) wProfitEl.textContent = wholesaleProfit.toFixed(2);
            if (wMarginEl) wMarginEl.textContent = wholesaleMargin.toFixed(2) + '% margin';
        }

        function recalcFromCost() {
            const cost = parseFloat(purchaseInput?.value) || 0;
            if (cost > 0 && autoRecalcSwitch && autoRecalcSwitch.checked) {
                const newRetail = cost * (1 + (retailMarkupPct / 100));
                const newWholesale = cost * (1 + (wholesaleMarkupPct / 100));
                if (retailInput) retailInput.value = newRetail.toFixed(2);
                if (wholesaleInput) wholesaleInput.value = newWholesale.toFixed(2);
            }
            updateMarginBadges();
        }

        const initialCost = parseFloat(purchaseInput?.value) || 0;
        const initialRetail = parseFloat(retailInput?.value) || 0;
        const initialWholesale = parseFloat(wholesaleInput?.value) || 0;
        if (initialCost > 0) {
            retailMarkupPct = Math.max(0, ((initialRetail - initialCost) / initialCost) * 100);
            wholesaleMarkupPct = Math.max(0, ((initialWholesale - initialCost) / initialCost) * 100);
        }

        purchaseInput?.addEventListener('input', recalcFromCost);
        retailInput?.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput?.value) || 0;
            const retail = parseFloat(this.value) || 0;
            if (cost > 0 && retail >= cost) {
                retailMarkupPct = ((retail - cost) / cost) * 100;
            }
            updateMarginBadges();
        });
        wholesaleInput?.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput?.value) || 0;
            const wholesale = parseFloat(this.value) || 0;
            if (cost > 0 && wholesale >= cost) {
                wholesaleMarkupPct = ((wholesale - cost) / cost) * 100;
            }
            updateMarginBadges();
        });

        // Retail markup preset clicks
        document.querySelectorAll('.btn-edit-retail-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                retailMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput?.value) || 0;
                if (retailInput) {
                    retailInput.value = (cost * (1 + (retailMarkupPct / 100))).toFixed(2);
                }
                updateMarginBadges();
            });
        });

        // Wholesale markup preset clicks
        document.querySelectorAll('.btn-edit-wholesale-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                wholesaleMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput?.value) || 0;
                if (wholesaleInput) {
                    wholesaleInput.value = (cost * (1 + (wholesaleMarkupPct / 100))).toFixed(2);
                }
                updateMarginBadges();
            });
        });

        // Initial run
        updateMarginBadges();
    })();
</script>
@endpush
@endsection
