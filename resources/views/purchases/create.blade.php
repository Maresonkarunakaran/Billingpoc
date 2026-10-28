@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-plus-circle text-primary mr-2"></i>Record New Purchase Order / Goods Receipt</h4>
        <span class="text-muted text-sm">Drives weighted average costing and updates branch warehouse inventory.</span>
    </div>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Purchases</a>
</div>

<form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
    @csrf
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Receiving Branch *</label>
                    <select name="branch_id" class="form-control form-control-sm font-weight-bold" required>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ session('active_branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} ({{ strtoupper($b->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Supplier *</label>
                    <select name="supplier_id" class="form-control form-control-sm" required>
                        <option value="">-- Select Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->company_name ?? 'Individual' }}) [Due: ₹{{ number_format($s->current_balance, 2) }}]</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Supplier Invoice No</label>
                    <input type="text" name="supplier_invoice_number" class="form-control form-control-sm" placeholder="E.g. INV-9842">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="text-xs font-weight-bold text-uppercase text-muted">Purchase Date *</label>
                    <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Items Table -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="font-weight-bold text-dark mb-0">Procured Line Items</h6>
            <button type="button" class="btn btn-primary btn-xs font-weight-bold" onclick="addPurchaseRow()">
                <i class="fas fa-plus mr-1"></i> Add Item Line
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 table-bordered" id="purchaseItemsTable">
                    <thead class="bg-light text-center text-xs text-uppercase text-muted">
                        <tr>
                            <th style="width: 40%;">Product</th>
                            <th style="width: 15%;">Quantity</th>
                            <th style="width: 15%;">Unit Cost (₹)</th>
                            <th style="width: 12%;">GST Rate (%)</th>
                            <th style="width: 15%;">Line Total (₹)</th>
                            <th style="width: 3%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Row 0 by default -->
                        <tr class="purchase-row">
                            <td>
                                <select name="items[0][product_id]" class="form-control form-control-sm product-select" required onchange="handleProductSelect(this, 0)">
                                    <option value="">-- Select Product --</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-cost="{{ $p->purchase_price }}" data-gst="{{ $p->gst_rate }}" data-unit="{{ $p->baseUnit->code }}">
                                            {{ $p->name }} [{{ $p->sku }}]
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control form-control-sm text-center font-weight-bold row-qty" value="10" min="1" step="1" required onchange="calcRowTotal(0)">
                            </td>
                            <td>
                                <input type="number" name="items[0][unit_cost]" class="form-control form-control-sm text-right font-weight-bold row-cost" value="0.00" min="0" step="0.01" required onchange="calcRowTotal(0)">
                            </td>
                            <td>
                                <select name="items[0][gst_rate]" class="form-control form-control-sm text-center row-gst" onchange="calcRowTotal(0)">
                                    <option value="0">0%</option>
                                    <option value="5">5%</option>
                                    <option value="12">12%</option>
                                    <option value="18" selected>18%</option>
                                    <option value="28">28%</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm text-right font-weight-bold row-total bg-light" readonly value="0.00">
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-xs text-danger" onclick="removePurchaseRow(this)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Settlement & Receipt Options -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-2 font-weight-bold text-sm">Goods Receipt & Notes</div>
                <div class="card-body p-3">
                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Stock Receipt Status</label>
                        <select name="status" class="form-control font-weight-bold">
                            <option value="received" selected>Received (Immediately update inventory stock)</option>
                            <option value="ordered">Ordered (Draft PO, pending arrival)</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">PO Notes / Terms</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Delivery schedule, freight terms..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-2 font-weight-bold text-sm">Financial Payment Settlement</div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Bill Amount:</span>
                        <h4 class="font-weight-bold text-success mb-0" id="grandTotalLbl">₹ 0.00</h4>
                    </div>

                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Initial Paid Amount (₹)</label>
                        <input type="number" name="paid_amount" id="paidAmountInput" class="form-control form-control-sm" value="0.00" min="0" step="0.5">
                    </div>

                    <div class="form-group mb-3">
                        <label class="text-xs font-weight-bold text-uppercase text-muted">Payment Mode</label>
                        <select name="payment_method" class="form-control form-control-sm">
                            <option value="bank_transfer">Bank Transfer (NEFT/RTGS)</option>
                            <option value="upi">UPI / Net Banking</option>
                            <option value="cheque">Cheque</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success btn-block font-weight-bold py-2 shadow-sm">
                        <i class="fas fa-check mr-1"></i> Finalize Purchase Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let rowIndex = 1;

    function handleProductSelect(select, idx) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        const row = select.closest('tr');
        const cost = parseFloat(opt.dataset.cost) || 0;
        const gst = parseFloat(opt.dataset.gst) || 18;

        row.querySelector('.row-cost').value = cost.toFixed(2);
        row.querySelector('.row-gst').value = gst;
        calcRowTotal(idx);
    }

    function calcRowTotal(idx) {
        const rows = document.querySelectorAll('.purchase-row');
        let grandTotal = 0;

        rows.forEach(r => {
            const qty = parseFloat(r.querySelector('.row-qty').value) || 0;
            const cost = parseFloat(r.querySelector('.row-cost').value) || 0;
            const gst = parseFloat(r.querySelector('.row-gst').value) || 0;

            const taxable = qty * cost;
            const tax = taxable * (gst / 100);
            const lineTotal = taxable + tax;

            r.querySelector('.row-total').value = lineTotal.toFixed(2);
            grandTotal += lineTotal;
        });

        document.getElementById('grandTotalLbl').innerText = '₹ ' + grandTotal.toFixed(2);
    }

    function addPurchaseRow() {
        const tbody = document.querySelector('#purchaseItemsTable tbody');
        const tr = document.createElement('tr');
        tr.className = 'purchase-row';
        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-control form-control-sm product-select" required onchange="handleProductSelect(this, ${rowIndex})">
                    <option value="">-- Select Product --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-cost="{{ $p->purchase_price }}" data-gst="{{ $p->gst_rate }}" data-unit="{{ $p->baseUnit->code }}">
                            {{ $p->name }} [{{ $p->sku }}]
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][quantity]" class="form-control form-control-sm text-center font-weight-bold row-qty" value="10" min="1" step="1" required onchange="calcRowTotal(${rowIndex})">
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][unit_cost]" class="form-control form-control-sm text-right font-weight-bold row-cost" value="0.00" min="0" step="0.01" required onchange="calcRowTotal(${rowIndex})">
            </td>
            <td>
                <select name="items[${rowIndex}][gst_rate]" class="form-control form-control-sm text-center row-gst" onchange="calcRowTotal(${rowIndex})">
                    <option value="0">0%</option>
                    <option value="5">5%</option>
                    <option value="12">12%</option>
                    <option value="18" selected>18%</option>
                    <option value="28">28%</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-right font-weight-bold row-total bg-light" readonly value="0.00">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-xs text-danger" onclick="removePurchaseRow(this)"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
    }

    function removePurchaseRow(btn) {
        const rows = document.querySelectorAll('.purchase-row');
        if (rows.length <= 1) {
            alert("A purchase order must contain at least one item line.");
            return;
        }
        btn.closest('tr').remove();
        calcRowTotal(0);
    }
</script>
@endpush
