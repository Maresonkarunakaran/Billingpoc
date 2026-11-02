@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-sliders-h text-warning mr-2"></i>Physical Stock Reconciliation & Adjustment</h4>
        <span class="text-muted text-sm">Adjust stock quantity for branch <strong>{{ $branch->name }}</strong>. Alert quantity only sets a warning threshold; add stock here to increase on-hand inventory. Base price, wholesale price, and retail price can be updated anytime as market costs fluctuate.</span>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Inventory</a>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="font-weight-bold text-dark mb-0">Record Manual Stock Adjustment & Pricing</h6>
                <span class="badge badge-warning text-dark font-weight-bold"><i class="fas fa-store mr-1"></i> {{ $branch->name }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('inventory.adjust.post') }}" method="POST" id="adjustStockForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Target Product *</label>
                        <select name="product_id" id="selectTargetProduct" class="form-control font-weight-bold" required>
                            <option value="">-- Choose Product to Adjust --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id', $selectedProductId) == $p->id ? 'selected' : '' }}
                                    data-cost="{{ number_format((float)$p->purchase_price, 2, '.', '') }}"
                                    data-retail="{{ number_format((float)$p->retail_price, 2, '.', '') }}"
                                    data-wholesale="{{ number_format((float)$p->wholesale_price, 2, '.', '') }}"
                                    data-unit="{{ $p->baseUnit->name }} ({{ $p->baseUnit->code }})">
                                    {{ $p->name }} [{{ $p->sku }}] &bull; Current Stock: {{ number_format($p->getStockInBranch($branch->id), 2) }} {{ $p->baseUnit->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-sm">Adjustment Type *</label>
                                <select name="type" id="adjustTypeSelect" class="form-control font-weight-bold" required>
                                    <option value="add" class="text-success" {{ old('type') === 'add' ? 'selected' : '' }}>+ Add Inventory (Found excess / Incoming new stock)</option>
                                    <option value="deduct" class="text-danger" {{ old('type') === 'deduct' ? 'selected' : '' }}>- Deduct Inventory (Damaged / Missing / Shrinkage)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-sm">Quantity to Adjust *</label>
                                <input type="number" name="quantity" id="adjustQuantityInput" class="form-control font-weight-bold" min="1" step="1" value="{{ old('quantity') }}" required placeholder="Whole number quantity">
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Configuration: Base Price, Wholesale Price, Retail Price -->
                    <div class="card border mb-3 shadow-none bg-light" id="productPricingCard">
                        <div class="card-header bg-white py-2 font-weight-bold text-sm d-flex justify-content-between align-items-center flex-wrap">
                            <span class="text-dark"><i class="fas fa-tags text-primary mr-2"></i>Product Pricing (Base Price, Wholesale Price & Retail Price)</span>
                            <span class="badge badge-light border text-primary"><i class="fas fa-calculator mr-1"></i>Dynamic Price Setter</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                <small class="text-muted"><i class="fas fa-info-circle text-info mr-1"></i>Base purchase price may increase or decrease anytime. You can update base, wholesale, and retail prices right here.</small>
                                <div class="custom-control custom-switch mt-1 mt-md-0">
                                    <input type="checkbox" class="custom-control-input" id="adjustAutoRecalcSwitch" checked>
                                    <label class="custom-control-label text-xs font-weight-bold text-primary" for="adjustAutoRecalcSwitch">Auto-calculate selling prices on cost change</label>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label class="text-xs font-weight-bold text-primary mb-1">Retail Selling Markup Presets</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-adjust-retail-markup" data-pct="10">+10%</button>
                                        <button type="button" class="btn btn-outline-primary btn-adjust-retail-markup" data-pct="15">+15%</button>
                                        <button type="button" class="btn btn-outline-primary btn-adjust-retail-markup" data-pct="20">+20%</button>
                                        <button type="button" class="btn btn-outline-primary btn-adjust-retail-markup" data-pct="25">+25%</button>
                                        <button type="button" class="btn btn-outline-primary btn-adjust-retail-markup" data-pct="30">+30%</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-xs font-weight-bold text-success mb-1">Wholesale Selling Markup Presets</label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button" class="btn btn-outline-success btn-adjust-wholesale-markup" data-pct="5">+5%</button>
                                        <button type="button" class="btn btn-outline-success btn-adjust-wholesale-markup" data-pct="8">+8%</button>
                                        <button type="button" class="btn btn-outline-success btn-adjust-wholesale-markup" data-pct="10">+10%</button>
                                        <button type="button" class="btn btn-outline-success btn-adjust-wholesale-markup" data-pct="12">+12%</button>
                                        <button type="button" class="btn btn-outline-success btn-adjust-wholesale-markup" data-pct="15">+15%</button>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <label class="font-weight-bold text-xs">Base Purchase Cost (₹)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold">₹</span></div>
                                        <input type="number" name="purchase_price" id="adjustPurchasePrice" class="form-control font-weight-bold" min="0" step="0.01" value="{{ old('purchase_price') }}" placeholder="0.00">
                                    </div>
                                    <small class="text-muted text-xs">Supplier / mandi purchase rate</small>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <label class="font-weight-bold text-xs text-primary">Retail Selling Price (₹)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white text-primary font-weight-bold">₹</span></div>
                                        <input type="number" name="retail_price" id="adjustRetailPrice" class="form-control font-weight-bold text-primary" min="0" step="0.01" value="{{ old('retail_price') }}" placeholder="0.00">
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                                        <span class="text-muted">Profit: ₹ <span id="adjustRetailProfit">0.00</span></span>
                                        <span class="text-primary badge badge-light border" id="adjustRetailMargin">0.00%</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="font-weight-bold text-xs text-success">Wholesale Selling Price (₹)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white text-success font-weight-bold">₹</span></div>
                                        <input type="number" name="wholesale_price" id="adjustWholesalePrice" class="form-control font-weight-bold text-success" min="0" step="0.01" value="{{ old('wholesale_price') }}" placeholder="0.00">
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                                        <span class="text-muted">Profit: ₹ <span id="adjustWholesaleProfit">0.00</span></span>
                                        <span class="text-success badge badge-light border" id="adjustWholesaleMargin">0.00%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-sm">Reason / Justification *</label>
                        <textarea name="reason" class="form-control" rows="2" required placeholder="E.g. Monthly physical audit count variance, transit carton breakage, supplier market rate increase...">{{ old('reason') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2 shadow-sm" onclick="return confirm('Confirm stock reconciliation and price adjustment? This will update the inventory balance, ledger, and product pricing immediately.')">
                        <i class="fas fa-check mr-1"></i> Apply Stock Adjustment & Update Pricing
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productSelect = document.getElementById('selectTargetProduct');
        const purchaseInput = document.getElementById('adjustPurchasePrice');
        const retailInput = document.getElementById('adjustRetailPrice');
        const wholesaleInput = document.getElementById('adjustWholesalePrice');
        const autoRecalcSwitch = document.getElementById('adjustAutoRecalcSwitch');

        let retailMarkupPct = 20;
        let wholesaleMarkupPct = 10;

        function updateMarginBadges() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const retail = parseFloat(retailInput.value) || 0;
            const wholesale = parseFloat(wholesaleInput.value) || 0;

            const retailProfit = retail - cost;
            const retailMargin = retail > 0 ? ((retailProfit / retail) * 100) : 0;
            const wholesaleProfit = wholesale - cost;
            const wholesaleMargin = wholesale > 0 ? ((wholesaleProfit / wholesale) * 100) : 0;

            document.getElementById('adjustRetailProfit').textContent = retailProfit.toFixed(2);
            document.getElementById('adjustRetailMargin').textContent = retailMargin.toFixed(2) + '% margin';
            document.getElementById('adjustWholesaleProfit').textContent = wholesaleProfit.toFixed(2);
            document.getElementById('adjustWholesaleMargin').textContent = wholesaleMargin.toFixed(2) + '% margin';
        }

        function recalcFromCost() {
            const cost = parseFloat(purchaseInput.value) || 0;
            if (cost > 0 && autoRecalcSwitch.checked) {
                const newRetail = cost * (1 + (retailMarkupPct / 100));
                const newWholesale = cost * (1 + (wholesaleMarkupPct / 100));
                retailInput.value = newRetail.toFixed(2);
                wholesaleInput.value = newWholesale.toFixed(2);
            }
            updateMarginBadges();
        }

        function loadSelectedProductPrices() {
            const selected = productSelect.options[productSelect.selectedIndex];
            if (selected && selected.value) {
                const cost = parseFloat(selected.dataset.cost) || 0;
                const retail = parseFloat(selected.dataset.retail) || 0;
                const wholesale = parseFloat(selected.dataset.wholesale) || 0;

                purchaseInput.value = cost.toFixed(2);
                retailInput.value = retail.toFixed(2);
                wholesaleInput.value = wholesale.toFixed(2);

                if (cost > 0) {
                    retailMarkupPct = Math.max(0, ((retail - cost) / cost) * 100);
                    wholesaleMarkupPct = Math.max(0, ((wholesale - cost) / cost) * 100);
                } else {
                    retailMarkupPct = 20;
                    wholesaleMarkupPct = 10;
                }
                updateMarginBadges();
            }
        }

        productSelect.addEventListener('change', loadSelectedProductPrices);

        purchaseInput.addEventListener('input', recalcFromCost);
        retailInput.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const retail = parseFloat(this.value) || 0;
            if (cost > 0 && retail >= cost) {
                retailMarkupPct = ((retail - cost) / cost) * 100;
            }
            updateMarginBadges();
        });
        wholesaleInput.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const wholesale = parseFloat(this.value) || 0;
            if (cost > 0 && wholesale >= cost) {
                wholesaleMarkupPct = ((wholesale - cost) / cost) * 100;
            }
            updateMarginBadges();
        });

        // Markup preset clicks
        document.querySelectorAll('.btn-adjust-retail-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                retailMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput.value) || 0;
                retailInput.value = (cost * (1 + (retailMarkupPct / 100))).toFixed(2);
                updateMarginBadges();
            });
        });

        document.querySelectorAll('.btn-adjust-wholesale-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                wholesaleMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput.value) || 0;
                wholesaleInput.value = (cost * (1 + (wholesaleMarkupPct / 100))).toFixed(2);
                updateMarginBadges();
            });
        });

        // Trigger on initial page load if product is pre-selected
        if (productSelect.value) {
            loadSelectedProductPrices();
        }
    });
</script>
@endpush
@endsection
