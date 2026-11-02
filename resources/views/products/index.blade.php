@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-boxes text-danger mr-2"></i>Product Catalog & Pricing</h4>
        <span class="text-muted text-sm">Configure products, SKUs, GST tax rates, wholesale thresholds and retail prices.</span>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-plus mr-1"></i> Add New Product
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('products.index') }}" method="GET" class="row">
            <div class="col-md-6 mb-2 mb-md-0">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Product Name, SKU, Barcode..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-right">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Search</button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-right">Cost (₹)</th>
                        <th class="text-right">Retail (₹)</th>
                        <th class="text-right">Wholesale (₹)</th>
                        <th class="text-center">GST Rate</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $stock = $p->getStockInBranch($branchId);
                        @endphp
                        <tr>
                            <td>
                                <div class="font-weight-bold text-dark">{{ $p->name }}</div>
                                <small class="text-muted">Base Unit: {{ $p->baseUnit->name }} ({{ $p->baseUnit->code }})</small>
                            </td>
                            <td>
                                <div class="font-weight-bold"><code>{{ $p->sku }}</code></div>
                                @if($p->barcode)
                                    <div class="text-xs text-muted mt-1">
                                        <i class="fas fa-barcode text-primary mr-1"></i><span class="font-weight-bold text-dark">{{ $p->barcode }}</span>
                                    </div>
                                @else
                                    <div class="text-xs text-muted mt-1">
                                        <span class="badge badge-light border text-muted">No Barcode</span>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $p->category?->name ?? 'General' }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($p->purchase_price, 2) }}</td>
                            <td class="text-right font-weight-bold text-primary">₹ {{ number_format($p->retail_price, 2) }}</td>
                            <td class="text-right font-weight-bold text-success">
                                ₹ {{ number_format($p->wholesale_price, 2) }}
                                <small class="text-muted d-block">(&ge;{{ number_format((float) $p->min_wholesale_qty, 2) }})</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-gst">{{ $p->gst_rate }}% {{ ucfirst($p->tax_type) }}</span>
                            </td>
                            <td class="text-center font-weight-bold {{ $stock <= 0 ? 'text-danger' : ($stock <= $p->alert_quantity ? 'text-warning' : 'text-success') }}">
                                {{ number_format($stock, 2) }} {{ $p->baseUnit->code }}
                            </td>
                            <td class="text-right text-nowrap">
                                @if($p->is_active)
                                    <a href="{{ route('inventory.adjust', ['product_id' => $p->id]) }}" class="btn btn-xs btn-outline-success mr-1" title="Add stock to active branch">
                                        <i class="fas fa-plus mr-1"></i> Add Stock
                                    </a>
                                @endif
                                <button type="button" class="btn btn-xs btn-outline-info mr-1 btn-open-set-price font-weight-bold"
                                        data-id="{{ $p->id }}"
                                        data-name="{{ $p->name }}"
                                        data-sku="{{ $p->sku }}"
                                        data-unit="{{ $p->baseUnit->name }} ({{ $p->baseUnit->code }})"
                                        data-cost="{{ number_format((float)$p->purchase_price, 2, '.', '') }}"
                                        data-retail="{{ number_format((float)$p->retail_price, 2, '.', '') }}"
                                        data-wholesale="{{ number_format((float)$p->wholesale_price, 2, '.', '') }}"
                                        data-action="{{ route('products.quick-price-update', $p->id) }}"
                                        title="Set Price: Base Price, Wholesale Price, Retail Price">
                                    <i class="fas fa-tags mr-1"></i> Set Price
                                </button>
                                <a href="{{ route('products.edit', $p->id) }}" class="btn btn-xs btn-outline-primary mr-1" title="Edit Product">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <form action="{{ route('products.toggle-status', $p->id) }}" method="POST" class="d-inline mr-1" onsubmit="return confirm('{{ $p->is_active ? 'Deactivate' : 'Activate' }} product \'{{ addslashes($p->name) }}\'?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-xs {{ $p->is_active ? 'btn-outline-warning' : 'btn-outline-secondary' }}" title="{{ $p->is_active ? 'Deactivate Product' : 'Activate Product' }}">
                                        <i class="fas {{ $p->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('products.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete product \'{{ addslashes($p->name) }}\'? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger font-weight-bold" title="Delete Product">
                                        <i class="fas fa-trash-alt mr-1"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No products found. Click "Add New Product" to create your catalog.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $products->links() }}
        </div>
    @endif
</div>

<!-- Set Product Prices Modal (Quickly update Base Price, Retail Price, Wholesale Price) -->
<div class="modal fade" id="setPriceModal" tabindex="-1" role="dialog" aria-labelledby="setPriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white py-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title font-weight-bold" id="setPriceModalLabel">
                    <i class="fas fa-tags mr-2"></i>Set Product Prices
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formSetPrice" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mb-3 border">
                        <div>
                            <div class="font-weight-bold text-dark" id="modalProductName">Product Name</div>
                            <small class="text-muted">SKU: <strong id="modalProductSku"></strong> | Unit: <span id="modalProductUnit"></span></small>
                        </div>
                        <span class="badge badge-info px-2 py-1"><i class="fas fa-bolt mr-1"></i>Quick Price Update</span>
                    </div>

                    <div class="alert alert-light border py-2 px-3 mb-3 text-xs text-muted">
                        <i class="fas fa-info-circle text-primary mr-1"></i>
                        Base purchase price increases or decreases over time. When you change Base Price, the Selling Price calculator updates margins and wholesale/retail prices automatically.
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm text-dark d-flex justify-content-between">
                            <span>Base Purchase Cost (₹) *</span>
                            <span class="badge badge-secondary font-weight-normal">Cost from supplier / mandi</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold">₹</span></div>
                            <input type="number" name="purchase_price" id="modalPurchasePrice" class="form-control font-weight-bold" required min="0" step="0.01">
                        </div>
                    </div>

                    <!-- Quick Markup Presets -->
                    <div class="bg-light p-3 rounded border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-xs font-weight-bold text-uppercase text-muted"><i class="fas fa-calculator mr-1"></i>Quick Markup Calculator</span>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="modalAutoRecalcSwitch" checked>
                                <label class="custom-control-label text-xs font-weight-bold text-primary" for="modalAutoRecalcSwitch">Auto-calculate on cost change</label>
                            </div>
                        </div>
                        <div class="row no-gutters">
                            <div class="col-6 pr-1">
                                <label class="text-xs font-weight-bold text-primary mb-1">Retail Markup %</label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-modal-retail-markup" data-pct="15">+15%</button>
                                    <button type="button" class="btn btn-outline-primary btn-modal-retail-markup" data-pct="20">+20%</button>
                                    <button type="button" class="btn btn-outline-primary btn-modal-retail-markup" data-pct="25">+25%</button>
                                    <button type="button" class="btn btn-outline-primary btn-modal-retail-markup" data-pct="30">+30%</button>
                                </div>
                            </div>
                            <div class="col-6 pl-1">
                                <label class="text-xs font-weight-bold text-success mb-1">Wholesale Markup %</label>
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button type="button" class="btn btn-outline-success btn-modal-wholesale-markup" data-pct="5">+5%</button>
                                    <button type="button" class="btn btn-outline-success btn-modal-wholesale-markup" data-pct="8">+8%</button>
                                    <button type="button" class="btn btn-outline-success btn-modal-wholesale-markup" data-pct="10">+10%</button>
                                    <button type="button" class="btn btn-outline-success btn-modal-wholesale-markup" data-pct="15">+15%</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm text-primary">Retail Selling Price (₹) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-primary font-weight-bold">₹</span></div>
                                <input type="number" name="retail_price" id="modalRetailPrice" class="form-control font-weight-bold text-primary" required min="0" step="0.01">
                            </div>
                            <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                                <span class="text-muted">Profit: ₹ <span id="modalRetailProfit">0.00</span></span>
                                <span class="text-primary badge badge-light border" id="modalRetailMargin">0.00% margin</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold text-sm text-success">Wholesale Selling Price (₹) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text bg-white text-success font-weight-bold">₹</span></div>
                                <input type="number" name="wholesale_price" id="modalWholesalePrice" class="form-control font-weight-bold text-success" required min="0" step="0.01">
                            </div>
                            <div class="mt-1 d-flex justify-content-between text-xs font-weight-bold">
                                <span class="text-muted">Profit: ₹ <span id="modalWholesaleProfit">0.00</span></span>
                                <span class="text-success badge badge-light border" id="modalWholesaleMargin">0.00% margin</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> Update & Save Prices
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formSetPrice');
        const purchaseInput = document.getElementById('modalPurchasePrice');
        const retailInput = document.getElementById('modalRetailPrice');
        const wholesaleInput = document.getElementById('modalWholesalePrice');
        const autoRecalcSwitch = document.getElementById('modalAutoRecalcSwitch');
        
        let currentRetailMarkupPct = 20;
        let currentWholesaleMarkupPct = 10;

        function updateModalMarginBadges() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const retail = parseFloat(retailInput.value) || 0;
            const wholesale = parseFloat(wholesaleInput.value) || 0;

            const retailProfit = retail - cost;
            const retailMargin = retail > 0 ? ((retailProfit / retail) * 100) : 0;
            const wholesaleProfit = wholesale - cost;
            const wholesaleMargin = wholesale > 0 ? ((wholesaleProfit / wholesale) * 100) : 0;

            document.getElementById('modalRetailProfit').textContent = retailProfit.toFixed(2);
            document.getElementById('modalRetailMargin').textContent = retailMargin.toFixed(2) + '% margin';
            document.getElementById('modalWholesaleProfit').textContent = wholesaleProfit.toFixed(2);
            document.getElementById('modalWholesaleMargin').textContent = wholesaleMargin.toFixed(2) + '% margin';
        }

        function recalcFromCost() {
            const cost = parseFloat(purchaseInput.value) || 0;
            if (cost > 0 && autoRecalcSwitch.checked) {
                const newRetail = cost * (1 + (currentRetailMarkupPct / 100));
                const newWholesale = cost * (1 + (currentWholesaleMarkupPct / 100));
                retailInput.value = newRetail.toFixed(2);
                wholesaleInput.value = newWholesale.toFixed(2);
            }
            updateModalMarginBadges();
        }

        // Open modal listener
        document.querySelectorAll('.btn-open-set-price').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                const sku = this.dataset.sku;
                const unit = this.dataset.unit;
                const cost = parseFloat(this.dataset.cost) || 0;
                const retail = parseFloat(this.dataset.retail) || 0;
                const wholesale = parseFloat(this.dataset.wholesale) || 0;
                const action = this.dataset.action;

                document.getElementById('modalProductName').textContent = name;
                document.getElementById('modalProductSku').textContent = sku;
                document.getElementById('modalProductUnit').textContent = unit;
                form.action = action;

                purchaseInput.value = cost.toFixed(2);
                retailInput.value = retail.toFixed(2);
                wholesaleInput.value = wholesale.toFixed(2);

                if (cost > 0) {
                    currentRetailMarkupPct = Math.max(0, ((retail - cost) / cost) * 100);
                    currentWholesaleMarkupPct = Math.max(0, ((wholesale - cost) / cost) * 100);
                } else {
                    currentRetailMarkupPct = 20;
                    currentWholesaleMarkupPct = 10;
                }

                updateModalMarginBadges();
                $('#setPriceModal').modal('show');
            });
        });

        purchaseInput.addEventListener('input', recalcFromCost);
        retailInput.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const retail = parseFloat(this.value) || 0;
            if (cost > 0 && retail >= cost) {
                currentRetailMarkupPct = ((retail - cost) / cost) * 100;
            }
            updateModalMarginBadges();
        });
        wholesaleInput.addEventListener('input', function() {
            const cost = parseFloat(purchaseInput.value) || 0;
            const wholesale = parseFloat(this.value) || 0;
            if (cost > 0 && wholesale >= cost) {
                currentWholesaleMarkupPct = ((wholesale - cost) / cost) * 100;
            }
            updateModalMarginBadges();
        });

        // Markup preset clicks
        document.querySelectorAll('.btn-modal-retail-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                currentRetailMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput.value) || 0;
                retailInput.value = (cost * (1 + (currentRetailMarkupPct / 100))).toFixed(2);
                updateModalMarginBadges();
            });
        });

        document.querySelectorAll('.btn-modal-wholesale-markup').forEach(btn => {
            btn.addEventListener('click', function() {
                currentWholesaleMarkupPct = parseFloat(this.dataset.pct) || 0;
                const cost = parseFloat(purchaseInput.value) || 0;
                wholesaleInput.value = (cost * (1 + (currentWholesaleMarkupPct / 100))).toFixed(2);
                updateModalMarginBadges();
            });
        });
    });
</script>
@endpush
@endsection
