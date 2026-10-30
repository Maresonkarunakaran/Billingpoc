@extends('layouts.app')

@push('styles')
<style>
    .pos-product-card {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
    }
    .pos-product-card:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .pos-product-card.out-of-stock {
        opacity: 0.55;
        cursor: not-allowed;
        background: #f8fafc;
    }
    .pos-order-product-search,
    .pos-customer-search {
        position: relative;
    }
    .pos-order-search-results {
        position: absolute;
        top: 100%;
        right: 0;
        left: 0;
        z-index: 1050;
        max-height: 280px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 0 0 6px 6px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.15);
    }
    .pos-order-search-result {
        display: block;
        width: 100%;
        padding: 0.6rem 0.75rem;
        border: 0;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
        text-align: left;
    }
    .pos-order-search-result:hover,
    .pos-order-search-result:focus {
        background: #eff6ff;
        outline: none;
    }
    .pos-order-search-result:disabled {
        color: #94a3b8;
        cursor: not-allowed;
    }
    .pos-customer-search-results {
        max-height: 240px;
    }
    .cart-table th { background: #f1f5f9; font-size: 0.75rem; text-transform: uppercase; color: #475569; }
    .cart-table td { vertical-align: middle !important; }
    .cart-row:hover { background-color: #f8fafc; }
    .tender-btn { min-width: 75px; font-weight: 600; }

    /* Clean Cart Quantity Input & Stepper (No overlapping browser spinners) */
    .cart-qty-input::-webkit-outer-spin-button,
    .cart-qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .cart-qty-input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
        width: 60px;
        min-width: 55px;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        background-color: #ffffff;
        border-color: #cbd5e1;
        text-align: center;
        padding: 0 4px;
        height: 32px;
    }
    .cart-qty-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        outline: none;
    }
    .cart-qty-btn {
        width: 32px;
        height: 32px;
        font-weight: 700;
        font-size: 1.1rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
    }
    .cart-qty-btn:hover {
        background-color: #e2e8f0;
        color: #0f172a;
        border-color: #94a3b8;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- LEFT PANEL: BARCODE, SEARCH & COMPACT PRODUCT CATALOG (5 COLS) -->
    <div class="col-lg-5 mb-3">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3">
                <!-- Barcode Input with Auto-Add -->
                <div class="mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                        </div>
                        <input type="text" id="barcodeInput" class="form-control font-weight-bold" placeholder="Scan Barcode / SKU and hit Enter..." autofocus>
                    </div>
                </div>

                <!-- Live Search -->
                <div class="d-flex">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search product name...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm ml-1" type="button" id="refreshProductsBtn" title="Refresh product stock" aria-label="Refresh product stock">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <!-- Category Pills -->
                <div class="d-flex overflow-auto mt-2 py-1" style="gap: 5px; white-space: nowrap;" id="categoryFilterContainer">
                    <button class="btn btn-xs btn-primary category-btn active" data-cat-id="">All Categories</button>
                    @foreach($categories as $cat)
                        <button class="btn btn-xs btn-outline-secondary category-btn" data-cat-id="{{ $cat->id }}">{{ $cat->name }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Compact Product Grid -->
        <div class="row" id="productGrid" style="max-height: 640px; overflow-y: auto;">
            <!-- Rendered dynamically by JavaScript -->
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                <p>Loading catalog items...</p>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL: CURRENT ORDER BILLING & CHECKOUT (7 COLS - INCREASED SPACE) -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <!-- Header: Pricing Mode & Customer Selection -->
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="font-weight-bold text-dark text-lg"><i class="fas fa-shopping-basket text-success mr-2"></i> Current Order Billing</span>
                    
                    <!-- Retail / Wholesale Switcher -->
                    <div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-primary active font-weight-bold" id="pricingRetailLabel">
                            <input type="radio" name="pricing_type_radio" id="pricingRetail" value="retail" checked> Retail Rate
                        </label>
                        <label class="btn btn-outline-primary font-weight-bold" id="pricingWholesaleLabel">
                            <input type="radio" name="pricing_type_radio" id="pricingWholesale" value="wholesale"> Wholesale Rate
                        </label>
                    </div>
                </div>

                <div class="pos-order-product-search mb-2">
                    <label class="sr-only" for="cartProductSearch">Search products to add to the current order</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input
                            type="search"
                            id="cartProductSearch"
                            class="form-control"
                            placeholder="Type to search and add a product..."
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="cartProductSearchResults"
                        >
                    </div>
                    <div id="cartProductSearchResults" class="pos-order-search-results d-none" role="listbox" aria-label="Product search results"></div>
                </div>

                <!-- Customer Search and Selection -->
                <div class="d-flex align-items-center" style="gap: 6px;">
                    <div class="pos-customer-search flex-grow-1" id="posCustomerSearch">
                        <label class="sr-only" for="customerSearch">Search customers by name, phone, or GSTIN</label>
                        <input
                            type="search"
                            id="customerSearch"
                            class="form-control form-control-sm"
                            placeholder="Search customer by name, phone, or GSTIN..."
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="customerSearchResults"
                        >
                        <div id="customerSearchResults" class="pos-order-search-results pos-customer-search-results d-none" role="listbox" aria-label="Customer search results"></div>
                    </div>
                    <select id="customerSelect" class="d-none" aria-hidden="true" tabindex="-1">
                        <option value="">Walk-in Customer (Cash / Non-GST)</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" data-state="{{ $c->state_code }}" data-gstin="{{ $c->gstin }}" data-balance="{{ $c->current_balance }}">
                                {{ $c->name }} ({{ $c->phone ?: 'No phone' }}) {{ $c->gstin ? '★ GST' : '' }} [Due: ₹{{ number_format($c->current_balance, 2) }}]
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#addCustomerModal" title="Add Customer">
                        <i class="fas fa-user-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Cart Items Table (Spacious with Generous Columns) -->
            <div class="card-body p-0" style="max-height: 480px; overflow-y: auto;">
                <table class="table table-hover mb-0 cart-table">
                    <thead>
                        <tr>
                            <th style="width: 44%;">Item Description</th>
                            <th class="text-center" style="width: 145px;">Quantity</th>
                            <th class="text-right" style="width: 18%;">Unit Price</th>
                            <th class="text-right" style="width: 22%;">Line Total</th>
                            <th class="text-center" style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartItemsList">
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-shopping-cart fa-3x mb-2 text-secondary"></i>
                                <h6 class="font-weight-bold text-dark">Current Order is Empty</h6>
                                <p class="text-muted text-sm mb-0">Scan barcodes with your scanner, search above, or select products from the left.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Order Financial Summary -->
            <div class="card-footer bg-light p-3 border-top">
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span class="text-muted">Subtotal (Before Tax):</span>
                    <span class="font-weight-semibold" id="lblSubtotal">₹ 0.00</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span class="text-muted">Discount:</span>
                    <span class="text-danger" id="lblDiscount">- ₹ 0.00</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span class="text-muted">Taxable Amount:</span>
                    <span class="font-weight-semibold" id="lblTaxable">₹ 0.00</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span class="text-muted" id="lblTaxTitle">GST (CGST + SGST):</span>
                    <span class="text-info font-weight-semibold" id="lblTax">₹ 0.00</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-2">
                    <span class="text-muted">Round Off:</span>
                    <span id="lblRoundOff">₹ 0.00</span>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-2 mb-3">
                    <h5 class="font-weight-bold text-dark mb-0">Total Payable:</h5>
                    <h3 class="font-weight-bold text-success mb-0" id="lblFinalAmount">₹ 0.00</h3>
                </div>

                <!-- Action Buttons -->
                <div class="row" style="gap: 6px;">
                    <div class="col">
                        <button class="btn btn-outline-danger btn-block py-2 font-weight-bold" id="btnClearCart">
                            <i class="fas fa-trash-alt mr-1"></i> Clear
                        </button>
                    </div>
                    <div class="col-8">
                        <button class="btn btn-success btn-block py-2 font-weight-bold" id="btnOpenPayment" disabled>
                            <i class="fas fa-credit-card mr-1"></i> Proceed to Pay (F2)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PAYMENT & CHECKOUT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-cash-register mr-2"></i>Checkout & Settle Payment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <!-- Left: Summary Details -->
                    <div class="col-md-5 border-right">
                        <div class="bg-light p-3 rounded mb-3">
                            <span class="text-xs text-uppercase font-weight-bold text-muted">Total Payable Amount</span>
                            <h2 class="font-weight-bold text-success mb-0 mt-1" id="modalFinalAmount">₹ 0.00</h2>
                            <small class="text-muted" id="modalTaxSummary">Inclusive of GST</small>
                        </div>

                        <div class="form-group mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Discount on Invoice</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="modalDiscountVal" class="form-control" value="0" min="0">
                                <div class="input-group-append">
                                    <select id="modalDiscountType" class="form-control form-control-sm">
                                        <option value="fixed">₹ (Fixed)</option>
                                        <option value="percentage">% (Percent)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">Order Notes / Ref</label>
                            <textarea id="modalNotes" class="form-control form-control-sm" rows="2" placeholder="Optional sale notes..."></textarea>
                        </div>
                    </div>

                    <!-- Right: Payment Method & Tender Amount -->
                    <div class="col-md-7">
                        <h6 class="font-weight-bold text-dark mb-3">Select Payment Method</h6>

                        <!-- Method Tabs -->
                        <div class="btn-group btn-group-toggle w-100 mb-3" data-toggle="buttons">
                            <label class="btn btn-outline-secondary active btn-sm" onclick="setPaymentMethod('cash')">
                                <input type="radio" name="pay_method" checked> <i class="fas fa-money-bill-wave text-success mr-1"></i> Cash
                            </label>
                            <label class="btn btn-outline-secondary btn-sm" onclick="setPaymentMethod('upi')">
                                <input type="radio" name="pay_method"> <i class="fas fa-qrcode text-primary mr-1"></i> UPI / QR
                            </label>
                            <label class="btn btn-outline-secondary btn-sm" onclick="setPaymentMethod('card')">
                                <input type="radio" name="pay_method"> <i class="fas fa-credit-card text-info mr-1"></i> Card
                            </label>
                            <label class="btn btn-outline-secondary btn-sm" onclick="setPaymentMethod('credit')">
                                <input type="radio" name="pay_method"> <i class="fas fa-user-clock text-warning mr-1"></i> Credit
                            </label>
                        </div>

                        <!-- Tendered Amount (For Cash & Change Calculation) -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Amount Tendered (₹)</label>
                            <input type="number" id="tenderAmount" class="form-control form-control-lg font-weight-bold text-primary" step="0.5">
                        </div>

                        <!-- Quick Cash Helper Buttons -->
                        <div class="mb-3 d-flex flex-wrap" style="gap:6px;" id="cashHelpers">
                            <button class="btn btn-sm btn-outline-secondary tender-btn" onclick="addTender(100)">+ ₹100</button>
                            <button class="btn btn-sm btn-outline-secondary tender-btn" onclick="addTender(200)">+ ₹200</button>
                            <button class="btn btn-sm btn-outline-secondary tender-btn" onclick="addTender(500)">+ ₹500</button>
                            <button class="btn btn-sm btn-outline-secondary tender-btn" onclick="addTender(1000)">+ ₹1000</button>
                            <button class="btn btn-sm btn-outline-secondary tender-btn" onclick="addTender(2000)">+ ₹2000</button>
                            <button class="btn btn-sm btn-primary tender-btn" onclick="exactTender()">Exact</button>
                        </div>

                        <!-- Reference No for UPI/Card -->
                        <div class="form-group mb-3 d-none" id="refNoGroup">
                            <label class="text-xs font-weight-bold text-uppercase text-muted">UPI UTR / Card Auth Code</label>
                            <input type="text" id="referenceNoInput" class="form-control" placeholder="E.g. 123456789012">
                        </div>

                        <!-- Change Return Box -->
                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-xs font-weight-bold text-uppercase text-muted">Change to Return</span>
                                <h4 class="font-weight-bold text-dark mb-0 mt-1" id="lblChangeDue">₹ 0.00</h4>
                            </div>
                            <div>
                                <span class="badge badge-success px-2 py-1" id="lblPayStatusBadge">READY</span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success btn-lg btn-block font-weight-bold shadow-sm" id="btnFinalSubmit">
                            <i class="fas fa-check-circle mr-2"></i> Finalize & Print Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADD CUSTOMER MODAL -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="quickCustomerForm" onsubmit="handleQuickCustomer(event)">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-1"></i> Register New Customer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Customer / Business Name *</label>
                        <input type="text" id="qcName" class="form-control" required placeholder="Full Name or Store Name">
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">Mobile Phone Number</label>
                        <input type="text" id="qcPhone" class="form-control" placeholder="10-digit mobile number">
                    </div>
                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold">GSTIN (Optional, for B2B tax invoice)</label>
                        <input type="text" id="qcGstin" class="form-control" maxlength="15" placeholder="15-character GSTIN">
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group mb-2">
                                <label class="text-xs font-weight-bold">State Code</label>
                                <input type="text" id="qcStateCode" class="form-control" value="33" required>
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="form-group mb-2">
                                <label class="text-xs font-weight-bold">State Name</label>
                                <input type="text" id="qcStateName" class="form-control" value="Tamil Nadu" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i> Save Customer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let productsList = [];
    let cart = [];
    let selectedPricingType = 'retail';
    let currentCategoryId = '';
    let activePaymentMethod = 'cash';
    let orderProductSearchTimer = null;
    let orderProductSearchController = null;
    let currentIdempotencyKey = 'pos-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    const branchStateCode = "{{ $branch->state_code }}";

    function getFilteredProducts() {
        const searchTerm = (document.getElementById('searchInput')?.value || '').trim().toLowerCase();
        return productsList.filter(p => {
            // Strict category check: if a category is selected, ONLY match products in that category
            if (currentCategoryId !== '' && currentCategoryId !== null && currentCategoryId !== undefined) {
                if (String(p.category_id) !== String(currentCategoryId)) {
                    return false;
                }
            }

            // Search term check: matches name, sku, or barcode
            if (searchTerm) {
                const name = (p.name || '').toLowerCase();
                const sku = (p.sku || '').toLowerCase();
                const barcode = (p.barcode || '').toLowerCase();
                if (!name.includes(searchTerm) && !sku.includes(searchTerm) && !barcode.includes(searchTerm)) {
                    return false;
                }
            }

            return true;
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadProducts();

        // Search and barcode handlers
        document.getElementById('barcodeInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleBarcodeScan(this.value.trim());
                this.value = '';
            }
        });

        document.getElementById('searchInput').addEventListener('input', function() {
            renderProducts(getFilteredProducts());
        });

        document.getElementById('refreshProductsBtn').addEventListener('click', refreshPosProducts);

        const orderProductSearch = document.getElementById('cartProductSearch');
        orderProductSearch.addEventListener('input', function() {
            searchProductsForOrder(this.value);
        });
        orderProductSearch.addEventListener('keydown', handleOrderProductSearchKeydown);

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.pos-order-product-search')) {
                closeOrderProductSearch();
            }

            if (!event.target.closest('#posCustomerSearch')) {
                closeCustomerSearchResults();
            }
        });

        // Category filter buttons - strictly show items in the selected category
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(b => {
                    b.classList.remove('active', 'btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('active', 'btn-primary');

                currentCategoryId = this.dataset.catId || '';
                renderProducts(getFilteredProducts());
            });
        });

        // Retail vs Wholesale switch
        document.querySelectorAll('input[name="pricing_type_radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                selectedPricingType = this.value;
                recalculateCart();
                renderProducts(getFilteredProducts());
            });
        });

        // Customer change
        document.getElementById('customerSelect').addEventListener('change', function() {
            updateCustomerSearchValue();
            recalculateCart();
        });
        document.getElementById('customerSearch').addEventListener('input', function() {
            document.getElementById('customerSelect').value = '';
            recalculateCart();
            renderCustomerSearchResults(this.value);
        });
        document.getElementById('customerSearch').addEventListener('keydown', handleCustomerSearchKeydown);
        document.getElementById('customerSearchResults').addEventListener('keydown', handleCustomerSearchKeydown);

        // Clear Cart
        document.getElementById('btnClearCart').addEventListener('click', function() {
            if (cart.length > 0 && confirm("Clear all items from current cart?")) {
                cart = [];
                recalculateCart();
            }
        });

        // Open Payment Modal
        document.getElementById('btnOpenPayment').addEventListener('click', function() {
            openPaymentModal();
        });

        // Tender input event
        document.getElementById('tenderAmount').addEventListener('input', updateChangeCalculation);
        document.getElementById('modalDiscountVal').addEventListener('input', recalculateCartModal);
        document.getElementById('modalDiscountType').addEventListener('change', recalculateCartModal);

        // Final submit
        document.getElementById('btnFinalSubmit').addEventListener('click', submitFinalSale);

        // Keyboard Shortcut F2 for Checkout
        window.addEventListener('keydown', function(e) {
            if (e.key === 'F2') {
                e.preventDefault();
                if (cart.length > 0) openPaymentModal();
            }
        });
    });

    // 1. Fetch products from API
    function loadProducts() {
        fetch("{{ route('pos.search') }}")
            .then(res => res.json())
            .then(data => {
                productsList = data;
                syncCartStock(productsList);
                renderProducts(getFilteredProducts());
            })
            .catch(err => {
                console.error("Error loading products:", err);
                Swal.fire('Stock Refresh Failed', 'Could not load the latest product stock. Please try again.', 'error');
            });
    }

    function refreshPosProducts() {
        const refreshButton = document.getElementById('refreshProductsBtn');
        refreshButton.disabled = true;
        refreshButton.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i>';

        fetch("{{ route('pos.search') }}")
            .then(response => {
                if (!response.ok) {
                    throw new Error('Could not load the latest product stock.');
                }

                return response.json();
            })
            .then(products => {
                productsList = products;
                syncCartStock(productsList);
                renderProducts(getFilteredProducts());
                recalculateCart();
            })
            .catch(error => {
                console.error('Error refreshing POS stock:', error);
                Swal.fire('Stock Refresh Failed', 'Could not load the latest product stock. Please try again.', 'error');
            })
            .finally(() => {
                refreshButton.disabled = false;
                refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
            });
    }

    function syncCartStock(products) {
        cart = cart.filter(item => {
            const product = products.find(product => product.id === item.product_id);
            if (!product) {
                return true;
            }

            item.stock = Math.floor(Number(product.stock));
            if (item.stock < 1) {
                return false;
            }

            item.quantity = Math.min(item.quantity, item.stock);
            return true;
        });
    }

    // 2. Render Products Grid
    function renderProducts(items) {
        const grid = document.getElementById('productGrid');
        if (!items || items.length === 0) {
            grid.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="fas fa-box-open fa-2x mb-2 text-secondary d-block"></i><p class="font-weight-bold mb-0">No products found in this category.</p></div>';
            return;
        }

        let html = '';
        items.forEach(p => {
            const price = selectedPricingType === 'wholesale' ? p.wholesale_price : p.retail_price;
            const availableStock = Math.floor(Number(p.stock));
            const isOutOfStock = availableStock < 1;
            const stockColor = availableStock > 10 ? 'text-success' : (availableStock > 0 ? 'text-warning' : 'text-danger');

            html += `
                <div class="col-6 mb-2">
                    <div class="pos-product-card p-2 h-100 d-flex flex-column justify-content-between ${isOutOfStock ? 'out-of-stock' : ''}" 
                         onclick="${isOutOfStock ? '' : `addToCart(${p.id})`}">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge badge-light border text-xs text-truncate" style="max-width: 90px;" title="${escapeHtml(p.sku)}">${escapeHtml(p.sku)}</span>
                                <span class="badge badge-gst text-xs">GST ${p.gst_rate}%</span>
                            </div>
                            <h6 class="font-weight-bold text-dark mb-1" style="font-size:0.85rem; line-height: 1.25;" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</h6>
                            ${p.barcode ? `<div class="text-xs text-muted mb-1"><i class="fas fa-barcode mr-1"></i>${escapeHtml(p.barcode)}</div>` : ''}
                        </div>
                        <div class="mt-1 pt-1 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <span class="font-weight-bold text-primary rupee" style="font-size: 0.98rem;">${price.toFixed(2)}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">/${escapeHtml(p.base_unit)}</small>
                            </div>
                            <div class="text-right">
                                <small class="font-weight-bold ${stockColor}" style="font-size: 0.72rem;">
                                    ${availableStock > 0 ? `${availableStock} in stock` : 'Out of Stock'}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);
    }

    function searchProductsForOrder(query) {
        clearTimeout(orderProductSearchTimer);
        if (orderProductSearchController) {
            orderProductSearchController.abort();
            orderProductSearchController = null;
        }

        const searchTerm = query.trim();
        if (searchTerm.length < 2) {
            closeOrderProductSearch();
            return;
        }

        renderOrderProductSearchMessage('<i class="fas fa-spinner fa-spin mr-2"></i>Searching products...');
        orderProductSearchTimer = setTimeout(async function() {
            orderProductSearchController = new AbortController();
            const controller = orderProductSearchController;

            try {
                const url = new URL("{{ route('pos.search') }}", window.location.origin);
                url.searchParams.set('q', searchTerm);
                const response = await fetch(url, { signal: controller.signal });

                if (!response.ok) {
                    throw new Error('Product search could not be completed.');
                }

                const products = await response.json();
                if (orderProductSearchController !== controller) {
                    return;
                }

                products.forEach(upsertProduct);
                renderOrderProductSearchResults(products);
            } catch (error) {
                if (error.name !== 'AbortError' && orderProductSearchController === controller) {
                    console.error('Error searching products for the order:', error);
                    renderOrderProductSearchMessage('Product search failed. Please try again.');
                }
            }
        }, 250);
    }

    function upsertProduct(product) {
        const existingIndex = productsList.findIndex(item => item.id === product.id);
        if (existingIndex === -1) {
            productsList.push(product);
        } else {
            productsList[existingIndex] = product;
        }
    }

    function renderOrderProductSearchMessage(message) {
        const results = document.getElementById('cartProductSearchResults');
        results.innerHTML = `<div class="px-3 py-2 text-sm text-muted">${message}</div>`;
        results.classList.remove('d-none');
        document.getElementById('cartProductSearch').setAttribute('aria-expanded', 'true');
    }

    function renderOrderProductSearchResults(products) {
        const results = document.getElementById('cartProductSearchResults');
        if (products.length === 0) {
            renderOrderProductSearchMessage('No matching products found.');
            return;
        }

        results.innerHTML = products.map((product, index) => {
            const price = selectedPricingType === 'wholesale' ? product.wholesale_price : product.retail_price;
            const availableStock = Math.floor(Number(product.stock));
            const stockLabel = availableStock > 0 ? `${availableStock} in stock` : 'Out of stock';

            return `
                <button
                    type="button"
                    class="pos-order-search-result"
                    role="option"
                    data-product-id="${product.id}"
                    aria-selected="false"
                    ${availableStock < 1 ? 'disabled' : ''}
                >
                    <span class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-dark">${escapeHtml(product.name)}</span>
                        <span class="font-weight-bold text-primary">₹${Number(price).toFixed(2)}</span>
                    </span>
                    <small class="d-flex justify-content-between text-muted">
                        <span>${escapeHtml(product.sku)}</span>
                        <span>${stockLabel}</span>
                    </small>
                </button>
            `;
        }).join('');

        results.querySelectorAll('[data-product-id]').forEach(button => {
            button.addEventListener('mousedown', event => event.preventDefault());
            button.addEventListener('click', function() {
                addToCart(Number(this.dataset.productId));
                document.getElementById('cartProductSearch').value = '';
                closeOrderProductSearch();
                document.getElementById('cartProductSearch').focus();
            });
        });

        results.classList.remove('d-none');
        document.getElementById('cartProductSearch').setAttribute('aria-expanded', 'true');
    }

    function handleOrderProductSearchKeydown(event) {
        const results = document.getElementById('cartProductSearchResults');
        const options = Array.from(results.querySelectorAll('button:not(:disabled)'));
        const focusedIndex = options.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' && options.length > 0) {
            event.preventDefault();
            options[Math.min(focusedIndex + 1, options.length - 1)].focus();
        } else if (event.key === 'ArrowUp' && options.length > 0) {
            event.preventDefault();
            if (focusedIndex <= 0) {
                document.getElementById('customerSearch').focus();
            } else {
                options[focusedIndex - 1].focus();
            }
        } else if (event.key === 'Enter' && options.length > 0) {
            event.preventDefault();
            options[focusedIndex >= 0 ? focusedIndex : 0].click();
        } else if (event.key === 'Escape') {
            closeOrderProductSearch();
        }
    }

    function closeOrderProductSearch() {
        clearTimeout(orderProductSearchTimer);
        if (orderProductSearchController) {
            orderProductSearchController.abort();
            orderProductSearchController = null;
        }

        const results = document.getElementById('cartProductSearchResults');
        results.classList.add('d-none');
        results.innerHTML = '';
        document.getElementById('cartProductSearch').setAttribute('aria-expanded', 'false');
    }

    function renderCustomerSearchResults(query) {
        const results = document.getElementById('customerSearchResults');
        const normalizedQuery = query.trim().toLocaleLowerCase();
        const customers = Array.from(document.getElementById('customerSelect').options)
            .filter(option => option.value)
            .filter(option => [
                option.dataset.name,
                option.dataset.phone,
                option.dataset.gstin
            ].some(value => (value || '').toLocaleLowerCase().includes(normalizedQuery)))
            .slice(0, 8);

        if (!normalizedQuery) {
            closeCustomerSearchResults();
            return;
        }

        const customerOptions = customers.map(option => `
            <button type="button" class="pos-order-search-result" role="option" data-customer-id="${escapeHtml(option.value)}" aria-selected="false">
                <span class="d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">${escapeHtml(option.dataset.name)}</span>
                    <span class="text-primary">${option.dataset.gstin ? 'GST Registered' : ''}</span>
                </span>
                <small class="d-flex justify-content-between text-muted">
                    <span>${escapeHtml(option.dataset.phone || 'No phone number')}${option.dataset.gstin ? ` · ${escapeHtml(option.dataset.gstin)}` : ''}</span>
                    <span>Due: ₹${Number(option.dataset.balance || 0).toFixed(2)}</span>
                </small>
            </button>
        `).join('');
        const emptyMessage = customers.length === 0
            ? '<div class="px-3 py-2 text-sm text-muted">No matching customers found.</div>'
            : '';

        results.innerHTML = `${customerOptions}${emptyMessage}
            <button type="button" class="pos-order-search-result text-muted" role="option" data-customer-id="" aria-selected="false">
                <span class="font-weight-bold">Walk-in Customer</span>
                <small class="d-block">Clear customer selection</small>
            </button>
        `;

        results.querySelectorAll('[data-customer-id]').forEach(button => {
            button.addEventListener('mousedown', event => event.preventDefault());
            button.addEventListener('click', function() {
                selectCustomer(this.dataset.customerId);
            });
        });

        results.classList.remove('d-none');
        document.getElementById('customerSearch').setAttribute('aria-expanded', 'true');
    }

    function selectCustomer(customerId) {
        const customerSelect = document.getElementById('customerSelect');
        customerSelect.value = customerId;
        updateCustomerSearchValue();
        closeCustomerSearchResults();
        recalculateCart();
        document.getElementById('customerSearch').focus();
    }

    function updateCustomerSearchValue() {
        const customerSelect = document.getElementById('customerSelect');
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        const customerSearch = document.getElementById('customerSearch');

        if (!selectedOption || !selectedOption.value) {
            customerSearch.value = '';
            return;
        }

        customerSearch.value = selectedOption.dataset.name
            + (selectedOption.dataset.phone ? ` (${selectedOption.dataset.phone})` : '');
    }

    function handleCustomerSearchKeydown(event) {
        const results = document.getElementById('customerSearchResults');
        const options = Array.from(results.querySelectorAll('button:not(:disabled)'));
        const focusedIndex = options.indexOf(document.activeElement);

        if (event.key === 'ArrowDown' && options.length > 0) {
            event.preventDefault();
            options[Math.min(focusedIndex + 1, options.length - 1)].focus();
        } else if (event.key === 'ArrowUp' && options.length > 0) {
            event.preventDefault();
            if (focusedIndex <= 0) {
                event.currentTarget.focus();
            } else {
                options[focusedIndex - 1].focus();
            }
        } else if (event.key === 'Enter' && options.length > 0) {
            event.preventDefault();
            options[0].click();
        } else if (event.key === 'Escape') {
            closeCustomerSearchResults();
        }
    }

    function closeCustomerSearchResults() {
        const results = document.getElementById('customerSearchResults');
        results.classList.add('d-none');
        results.innerHTML = '';
        document.getElementById('customerSearch').setAttribute('aria-expanded', 'false');
    }

    // 3. Add product to cart
    function addToCart(productId) {
        const product = productsList.find(p => p.id === productId);
        if (!product) return;
        const availableStock = Math.floor(Number(product.stock));

        if (availableStock < 1) {
            Swal.fire('Out of Stock', `'${product.name}' has no available inventory in this branch!`, 'warning');
            return;
        }

        const existing = cart.find(c => c.product_id === productId);
        if (existing) {
            if (existing.quantity + 1 > availableStock) {
                Swal.fire('Stock Limit Exceeded', `Only ${availableStock} whole units are available in branch. Cannot exceed stock.`, 'warning');
                return;
            }
            existing.quantity += 1;
        } else {
            const initialQty = (selectedPricingType === 'wholesale' && product.min_wholesale_qty > 1) 
                ? Math.min(Math.floor(Number(product.min_wholesale_qty)), availableStock)
                : 1;

            cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                stock: availableStock,
                unit_id: product.base_unit_id,
                base_unit_name: product.base_unit,
                retail_price: product.retail_price,
                wholesale_price: product.wholesale_price,
                gst_rate: product.gst_rate,
                tax_type: product.tax_type,
                quantity: initialQty,
                discount_amount: 0.0
            });
        }

        recalculateCart();
    }

    // 4. Handle Barcode scanning
    function handleBarcodeScan(barcode) {
        if (!barcode) return;
        const product = productsList.find(p => p.barcode === barcode || p.sku.toLowerCase() === barcode.toLowerCase());
        if (product) {
            addToCart(product.id);
        } else {
            // Live query server
            fetch("{{ route('pos.search') }}?barcode=" + encodeURIComponent(barcode))
                .then(r => r.json())
                .then(res => {
                    if (res && res.length > 0) {
                        const p = res[0];
                        if (!productsList.find(x => x.id === p.id)) productsList.push(p);
                        addToCart(p.id);
                    } else {
                        Swal.fire('Not Found', `No product found matching barcode '${barcode}'`, 'info');
                    }
                });
        }
    }

    // 5. Update Cart item quantity
    function updateQty(idx, delta) {
        const item = cart[idx];
        if (!item) return;

        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            cart.splice(idx, 1);
        } else if (newQty > item.stock) {
            Swal.fire('Stock Limit', `Cannot exceed available branch stock of ${item.stock} whole units.`, 'warning');
            return;
        } else {
            item.quantity = newQty;
        }

        recalculateCart();
    }

    function setQtyDirect(idx, val) {
        const item = cart[idx];
        const num = Number(val);
        if (!Number.isInteger(num)) {
            Swal.fire('Invalid Quantity', 'Quantity must be a whole number.', 'warning');
            recalculateCart();
            return;
        }

        if (isNaN(num) || num <= 0) {
            cart.splice(idx, 1);
        } else if (num > item.stock) {
            Swal.fire('Stock Limit', `Cannot exceed available branch stock of ${item.stock} whole units.`, 'warning');
            item.quantity = item.stock;
        } else {
            item.quantity = num;
        }
        recalculateCart();
    }

    function removeFromCart(idx) {
        cart.splice(idx, 1);
        recalculateCart();
    }

    // 6. Recalculate Cart Calculations with GST breakdown
    function recalculateCart() {
        const tbody = document.getElementById('cartItemsList');
        if (cart.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-2x mb-2 text-secondary"></i>
                        <p class="mb-0">Cart is empty. Scan barcodes or click items on the left.</p>
                    </td>
                </tr>
            `;
            document.getElementById('lblSubtotal').innerText = '₹ 0.00';
            document.getElementById('lblDiscount').innerText = '- ₹ 0.00';
            document.getElementById('lblTaxable').innerText = '₹ 0.00';
            document.getElementById('lblTax').innerText = '₹ 0.00';
            document.getElementById('lblRoundOff').innerText = '₹ 0.00';
            document.getElementById('lblFinalAmount').innerText = '₹ 0.00';
            document.getElementById('btnOpenPayment').disabled = true;
            return;
        }

        const customerSelect = document.getElementById('customerSelect');
        const selectedCustOpt = customerSelect.options[customerSelect.selectedIndex];
        const customerState = selectedCustOpt ? selectedCustOpt.dataset.state : branchStateCode;
        const isInterstate = customerState && customerState !== branchStateCode;

        let subtotal = 0.0;
        let taxableSubtotal = 0.0;
        let totalTax = 0.0;

        let rowsHtml = '';
        cart.forEach((item, idx) => {
            const price = selectedPricingType === 'wholesale' ? item.wholesale_price : item.retail_price;
            const lineGross = price * item.quantity;
            const netLine = Math.max(0, lineGross - item.discount_amount);

            let taxable = 0;
            let tax = 0;

            if (item.tax_type === 'inclusive') {
                taxable = netLine / (1 + (item.gst_rate / 100));
                tax = netLine - taxable;
            } else {
                taxable = netLine;
                tax = taxable * (item.gst_rate / 100);
            }

            subtotal += lineGross;
            taxableSubtotal += taxable;
            totalTax += tax;

            const lineTotal = taxable + tax;

            rowsHtml += `
                <tr class="cart-row">
                    <td>
                        <div class="font-weight-bold text-dark text-sm">${escapeHtml(item.name)}</div>
                        <span class="text-xs text-muted">₹${price.toFixed(2)} &bull; GST ${item.gst_rate}%</span>
                    </td>
                    <td class="text-center">
                        <div class="input-group input-group-sm justify-content-center mx-auto" style="width: 124px;">
                            <div class="input-group-prepend">
                                <button class="btn cart-qty-btn" type="button" onclick="updateQty(${idx}, -1)" title="Decrease Quantity" aria-label="Decrease Quantity">&minus;</button>
                            </div>
                            <input type="number" class="form-control text-center font-weight-bold cart-qty-input" min="1" max="${item.stock}" step="1"
                                   value="${item.quantity}" onchange="setQtyDirect(${idx}, this.value)" aria-label="Item Quantity">
                            <div class="input-group-append">
                                <button class="btn cart-qty-btn" type="button" onclick="updateQty(${idx}, 1)" title="Increase Quantity" aria-label="Increase Quantity">&plus;</button>
                            </div>
                        </div>
                    </td>
                    <td class="text-right font-weight-semibold text-sm">₹${price.toFixed(2)}</td>
                    <td class="text-right font-weight-bold text-dark text-sm">₹${lineTotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm text-danger p-1" onclick="removeFromCart(${idx})" title="Remove Item"><i class="fas fa-times fa-lg"></i></button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = rowsHtml;

        const grossTotal = taxableSubtotal + totalTax;
        const rounded = Math.round(grossTotal);
        const roundOff = (rounded - grossTotal).toFixed(2);

        document.getElementById('lblSubtotal').innerText = '₹ ' + subtotal.toFixed(2);
        document.getElementById('lblTaxable').innerText = '₹ ' + taxableSubtotal.toFixed(2);
        document.getElementById('lblTaxTitle').innerText = isInterstate ? 'IGST (Inter-state):' : 'CGST + SGST:';
        document.getElementById('lblTax').innerText = '₹ ' + totalTax.toFixed(2);
        document.getElementById('lblRoundOff').innerText = (roundOff >= 0 ? '+ ₹ ' : '- ₹ ') + Math.abs(roundOff);
        document.getElementById('lblFinalAmount').innerText = '₹ ' + rounded.toFixed(2);
        document.getElementById('btnOpenPayment').disabled = false;
    }

    // 7. Open Payment Modal
    function openPaymentModal() {
        const finalAmountStr = document.getElementById('lblFinalAmount').innerText.replace('₹', '').trim();
        const finalAmt = parseFloat(finalAmountStr) || 0;

        document.getElementById('modalFinalAmount').innerText = '₹ ' + finalAmt.toFixed(2);
        document.getElementById('tenderAmount').value = finalAmt.toFixed(2);
        updateChangeCalculation();

        $('#paymentModal').modal('show');
    }

    function setPaymentMethod(method) {
        activePaymentMethod = method;
        const refGrp = document.getElementById('refNoGroup');
        const tenderInput = document.getElementById('tenderAmount');

        if (method === 'upi' || method === 'card') {
            refGrp.classList.remove('d-none');
        } else {
            refGrp.classList.add('d-none');
        }

        if (method === 'credit') {
            tenderInput.value = '0.00';
        } else {
            exactTender();
        }
        updateChangeCalculation();
    }

    function addTender(amt) {
        const cur = parseFloat(document.getElementById('tenderAmount').value) || 0;
        document.getElementById('tenderAmount').value = (cur + amt).toFixed(2);
        updateChangeCalculation();
    }

    function exactTender() {
        const finalAmt = parseFloat(document.getElementById('modalFinalAmount').innerText.replace('₹', '').trim()) || 0;
        document.getElementById('tenderAmount').value = finalAmt.toFixed(2);
        updateChangeCalculation();
    }

    function updateChangeCalculation() {
        const finalAmt = parseFloat(document.getElementById('modalFinalAmount').innerText.replace('₹', '').trim()) || 0;
        const tendered = parseFloat(document.getElementById('tenderAmount').value) || 0;
        const changeDue = Math.max(0, tendered - finalAmt);

        document.getElementById('lblChangeDue').innerText = '₹ ' + changeDue.toFixed(2);

        const badge = document.getElementById('lblPayStatusBadge');
        if (activePaymentMethod === 'credit') {
            badge.className = 'badge badge-warning px-2 py-1';
            badge.innerText = 'CREDIT SALE';
        } else if (tendered >= finalAmt) {
            badge.className = 'badge badge-success px-2 py-1';
            badge.innerText = 'PAID IN FULL';
        } else if (tendered > 0) {
            badge.className = 'badge badge-info px-2 py-1';
            badge.innerText = 'PARTIAL PAYMENT';
        } else {
            badge.className = 'badge badge-danger px-2 py-1';
            badge.innerText = 'UNPAID';
        }
    }

    function recalculateCartModal() {
        recalculateCart();
        const baseAmount = parseFloat(document.getElementById('lblFinalAmount').innerText.replace('₹', '').trim()) || 0;
        const discVal = parseFloat(document.getElementById('modalDiscountVal').value) || 0;
        const discType = document.getElementById('modalDiscountType').value;

        let finalDiscount = 0;
        if (discType === 'percentage' && discVal > 0) {
            finalDiscount = (baseAmount * discVal) / 100;
        } else {
            finalDiscount = discVal;
        }

        const discountedTotal = Math.max(0, Math.round(baseAmount - finalDiscount));
        document.getElementById('modalFinalAmount').innerText = '₹ ' + discountedTotal.toFixed(2);
        document.getElementById('tenderAmount').value = discountedTotal.toFixed(2);
        updateChangeCalculation();
    }

    // 8. Final Submit Checkout
    function submitFinalSale() {
        if (cart.length === 0) return;

        const customerId = document.getElementById('customerSelect').value || null;
        const tendered = parseFloat(document.getElementById('tenderAmount').value) || 0;
        const finalAmt = parseFloat(document.getElementById('modalFinalAmount').innerText.replace('₹', '').trim()) || 0;

        const submitBtn = document.getElementById('btnFinalSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Finalizing Invoice...';

        const payments = [];
        if (activePaymentMethod === 'credit') {
            // Unpaid / Credit sale
        } else {
            const payAmt = Math.min(tendered, finalAmt);
            payments.push({
                payment_method: activePaymentMethod,
                amount: payAmt > 0 ? payAmt : finalAmt,
                reference_no: document.getElementById('referenceNoInput').value || null
            });
        }

        const payload = {
            branch_id: {{ $branch->id }},
            customer_id: customerId,
            pricing_type: selectedPricingType,
            idempotency_key: currentIdempotencyKey,
            discount_type: document.getElementById('modalDiscountType').value,
            discount_amount: parseFloat(document.getElementById('modalDiscountVal').value) || 0,
            notes: document.getElementById('modalNotes').value || null,
            items: cart.map(c => ({
                product_id: c.product_id,
                quantity: c.quantity,
                unit_id: c.unit_id,
                discount_amount: c.discount_amount
            })),
            payments: payments
        };

        fetch("{{ route('pos.checkout') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalize & Print Invoice';

            if (data.success) {
                $('#paymentModal').modal('hide');

                Swal.fire({
                    title: 'Sale Finalized!',
                    html: `Invoice Number: <strong>${data.invoice_number}</strong><br>Total Amount: <strong>₹ ${data.final_amount}</strong>`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-print mr-1"></i> Print Receipt',
                    cancelButtonText: '<i class="fas fa-plus mr-1"></i> Next Sale',
                    confirmButtonColor: '#10b981',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(data.receipt_url, '_blank', 'width=450,height=650');
                    }
                    // Reset cart and generate new idempotency key
                    cart = [];
                    currentIdempotencyKey = 'pos-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
                    recalculateCart();
                    loadProducts(); // refresh branch stock balances
                });
            } else {
                Swal.fire('Checkout Error', data.error || 'Failed to finalize transaction.', 'error');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Finalize & Print Invoice';
            Swal.fire('Error', 'Network or server error during checkout: ' + err, 'error');
        });
    }

    // 9. Quick Add Customer
    function handleQuickCustomer(e) {
        e.preventDefault();
        const payload = {
            name: document.getElementById('qcName').value,
            phone: document.getElementById('qcPhone').value,
            gstin: document.getElementById('qcGstin').value,
            state_code: document.getElementById('qcStateCode').value,
            state_name: document.getElementById('qcStateName').value
        };

        fetch("{{ route('customers.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(() => {
            $('#addCustomerModal').modal('hide');
            Swal.fire('Customer Added', 'Customer saved. Please select them from the list.', 'success')
                .then(() => window.location.reload());
        });
    }
</script>
@endpush
