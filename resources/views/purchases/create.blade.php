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
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="text-xs font-weight-bold text-uppercase text-muted mb-0">Supplier *</label>
                        <button type="button" class="btn btn-link btn-xs text-primary font-weight-bold p-0" data-toggle="modal" data-target="#quickSupplierModal" title="Add / Manage Suppliers">
                            <i class="fas fa-plus-circle mr-1"></i>+ Add / Manage Suppliers
                        </button>
                    </div>
                    <div class="input-group input-group-sm">
                        <select name="supplier_id" id="supplierSelect" class="form-control form-control-sm font-weight-bold" required>
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->company_name ? ' (' . $s->company_name . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#quickSupplierModal" title="Add / Manage Suppliers">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <button type="button" id="btnDeleteSelectedSupplier" class="btn btn-outline-danger" title="Delete Selected Supplier" style="display: none;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
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
                        <input type="number" name="paid_amount" id="paidAmountInput" class="form-control form-control-sm" value="0.00" min="0" step="0.01" placeholder="0.00">
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

<!-- QUICK ADD & MANAGE SUPPLIER MODAL -->
<div class="modal fade" id="quickSupplierModal" tabindex="-1" role="dialog" aria-labelledby="quickSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title font-weight-bold text-sm" id="quickSupplierModalLabel">
                    <i class="fas fa-truck mr-1"></i> Add & Manage Suppliers
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body p-3">
                <!-- Add New Supplier Form (Due option removed) -->
                <form id="quickSupplierForm" class="p-3 bg-light rounded border mb-3">
                    @csrf
                    <div id="quickSupplierAlert" class="alert alert-danger d-none py-1 px-2 text-xs mb-2"></div>
                    <label class="font-weight-bold text-xs text-uppercase text-muted d-block mb-2">
                        <i class="fas fa-user-plus text-primary mr-1"></i> Register New Supplier
                    </label>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">Supplier / Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" id="qsName" name="name" class="form-control form-control-sm" required placeholder="e.g. Ramesh Kumar">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">Business / Company Name</label>
                            <input type="text" id="qsCompany" name="company_name" class="form-control form-control-sm" placeholder="e.g. Sri Murugan Traders">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">Phone Number</label>
                            <input type="text" id="qsPhone" name="phone" class="form-control form-control-sm" placeholder="10-digit mobile number">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">GSTIN <small class="text-muted">(Optional)</small></label>
                            <input type="text" id="qsGstin" name="gstin" class="form-control form-control-sm" maxlength="15" placeholder="15-character GSTIN">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">State Code <span class="text-danger">*</span></label>
                            <input type="text" id="qsStateCode" name="state_code" class="form-control form-control-sm" value="33" required placeholder="33">
                        </div>
                        <div class="col-8 mb-2">
                            <label class="text-xs font-weight-bold text-dark mb-1">State Name <span class="text-danger">*</span></label>
                            <input type="text" id="qsStateName" name="state_name" class="form-control form-control-sm" value="Tamil Nadu" required placeholder="Tamil Nadu">
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label class="text-xs font-weight-bold text-dark mb-1">Address <small class="text-muted">(Optional)</small></label>
                        <textarea id="qsAddress" name="address" class="form-control form-control-sm" rows="1" placeholder="Warehouse / Shop Address"></textarea>
                    </div>

                    <div class="text-right">
                        <button type="submit" id="saveQuickSupplierBtn" class="btn btn-primary btn-sm font-weight-bold px-3 shadow-xs">
                            <i class="fas fa-save mr-1"></i> Save & Select Supplier
                        </button>
                    </div>
                </form>

                <!-- Existing Suppliers List with Delete Option -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="font-weight-bold text-xs text-uppercase text-muted mb-0">
                        Existing Suppliers (<span id="modalSupplierCount">{{ count($suppliers) }}</span>)
                    </label>
                    <small class="text-muted text-xs">Delete any supplier name directly from here</small>
                </div>

                <div class="table-responsive border rounded bg-white" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0" id="modalSuppliersTable">
                        <thead class="bg-light text-muted text-xs">
                            <tr>
                                <th style="width: 50%;">Supplier Name</th>
                                <th style="width: 30%;">Company</th>
                                <th style="width: 20%;" class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="modalSuppliersList">
                            @forelse($suppliers as $s)
                                <tr id="supplierRow_{{ $s->id }}">
                                    <td class="font-weight-medium text-dark align-middle">{{ $s->name }}</td>
                                    <td class="align-middle text-muted">{{ $s->company_name ?: '---' }}</td>
                                    <td class="text-right align-middle">
                                        <button type="button" class="btn btn-xs btn-outline-danger btn-modal-delete-supplier" data-id="{{ $s->id }}" data-name="{{ $s->name }}" title="Delete Supplier">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noSuppliersRow">
                                    <td colspan="3" class="text-center text-muted py-3">No suppliers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
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

    // Toggle Delete Selected Supplier button on dropdown change
    const supplierSelect = document.getElementById('supplierSelect');
    const btnDeleteSelectedSupplier = document.getElementById('btnDeleteSelectedSupplier');

    function syncDeleteButtonVisibility() {
        if (supplierSelect && btnDeleteSelectedSupplier) {
            if (supplierSelect.value) {
                btnDeleteSelectedSupplier.style.display = 'inline-block';
            } else {
                btnDeleteSelectedSupplier.style.display = 'none';
            }
        }
    }

    if (supplierSelect) {
        supplierSelect.addEventListener('change', syncDeleteButtonVisibility);
        syncDeleteButtonVisibility();
    }

    // Supplier Delete Handler (works for both dropdown delete button and modal table delete buttons)
    function executeDeleteSupplier(supplierId, supplierName) {
        if (!confirm(`Are you sure you want to delete supplier "${supplierName}"?`)) {
            return;
        }

        fetch("{{ url('/purchases/delete-supplier') }}/" + supplierId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(result => {
            if (result.status >= 200 && result.status < 300) {
                // 1. Remove option from supplierSelect
                const opt = supplierSelect.querySelector(`option[value="${supplierId}"]`);
                if (opt) {
                    if (supplierSelect.value == supplierId) {
                        supplierSelect.value = '';
                        syncDeleteButtonVisibility();
                    }
                    opt.remove();
                }

                // 2. Remove row from modal table
                const row = document.getElementById(`supplierRow_${supplierId}`);
                if (row) {
                    row.remove();
                }

                // 3. Update count
                const countBadge = document.getElementById('modalSupplierCount');
                if (countBadge) {
                    const currentCount = parseInt(countBadge.innerText, 10) || 1;
                    countBadge.innerText = Math.max(0, currentCount - 1);
                }

                // If no suppliers remain in table, show empty row
                const tbody = document.getElementById('modalSuppliersList');
                if (tbody && tbody.children.length === 0) {
                    tbody.innerHTML = '<tr id="noSuppliersRow"><td colspan="3" class="text-center text-muted py-3">No suppliers found.</td></tr>';
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Supplier Removed',
                        text: result.body.message || `Supplier "${supplierName}" has been deleted.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert(result.body.message || `Supplier "${supplierName}" deleted successfully.`);
                }
            } else {
                alert(result.body.message || 'Failed to delete supplier.');
            }
        })
        .catch(err => {
            alert('Network or server error while deleting supplier.');
        });
    }

    // Delete selected supplier from input group button
    if (btnDeleteSelectedSupplier) {
        btnDeleteSelectedSupplier.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedId = supplierSelect.value;
            if (!selectedId) return;
            const selectedText = supplierSelect.options[supplierSelect.selectedIndex].text;
            executeDeleteSupplier(selectedId, selectedText);
        });
    }

    // Delete supplier from modal table
    $(document).on('click', '.btn-modal-delete-supplier', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        executeDeleteSupplier(id, name);
    });

    // Quick Add Supplier AJAX Submission (Due Option Removed)
    const quickSupplierForm = document.getElementById('quickSupplierForm');
    if (quickSupplierForm) {
        quickSupplierForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('saveQuickSupplierBtn');
            const alertBox = document.getElementById('quickSupplierAlert');
            alertBox.classList.add('d-none');
            alertBox.innerText = '';
            
            const payload = {
                name: document.getElementById('qsName').value.trim(),
                company_name: document.getElementById('qsCompany').value.trim() || null,
                phone: document.getElementById('qsPhone').value.trim() || null,
                gstin: document.getElementById('qsGstin').value.trim() || null,
                state_code: document.getElementById('qsStateCode').value.trim() || '33',
                state_name: document.getElementById('qsStateName').value.trim() || 'Tamil Nadu',
                address: document.getElementById('qsAddress').value.trim() || null
            };

            if (!payload.name) {
                alertBox.innerText = 'Supplier name is required.';
                alertBox.classList.remove('d-none');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';

            fetch("{{ route('purchases.quick-supplier') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(result => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save & Select Supplier';

                if (result.status >= 200 && result.status < 300 && result.body.supplier) {
                    const sup = result.body.supplier;
                    const label = `${sup.name}${sup.company_name ? ' (' + sup.company_name + ')' : ''}`;
                    
                    // Add to dropdown and select
                    const opt = new Option(label, sup.id, true, true);
                    supplierSelect.appendChild(opt);
                    supplierSelect.value = sup.id;
                    syncDeleteButtonVisibility();
                    if (typeof $ !== 'undefined') {
                        $(supplierSelect).trigger('change');
                        $('#quickSupplierModal').modal('hide');
                    }

                    // Add to modal table
                    const tbody = document.getElementById('modalSuppliersList');
                    const noRow = document.getElementById('noSuppliersRow');
                    if (noRow) noRow.remove();

                    const newRow = document.createElement('tr');
                    newRow.id = `supplierRow_${sup.id}`;
                    newRow.innerHTML = `
                        <td class="font-weight-medium text-dark align-middle">${$('<div>').text(sup.name).html()}</td>
                        <td class="align-middle text-muted">${sup.company_name ? $('<div>').text(sup.company_name).html() : '---'}</td>
                        <td class="text-right align-middle">
                            <button type="button" class="btn btn-xs btn-outline-danger btn-modal-delete-supplier" data-id="${sup.id}" data-name="${$('<div>').text(sup.name).html()}" title="Delete Supplier">
                                <i class="fas fa-trash mr-1"></i> Delete
                            </button>
                        </td>
                    `;
                    tbody.appendChild(newRow);

                    // Update count
                    const countBadge = document.getElementById('modalSupplierCount');
                    if (countBadge) {
                        const currentCount = parseInt(countBadge.innerText, 10) || 0;
                        countBadge.innerText = currentCount + 1;
                    }

                    quickSupplierForm.reset();
                    document.getElementById('qsStateCode').value = '33';
                    document.getElementById('qsStateName').value = 'Tamil Nadu';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Supplier Added!',
                            text: `"${sup.name}" has been registered and selected for this purchase order.`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    let errMsg = result.body.message || 'Failed to save supplier.';
                    if (result.body.errors) {
                        errMsg = Object.values(result.body.errors).flat().join('<br>');
                    }
                    alertBox.innerHTML = errMsg;
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save & Select Supplier';
                alertBox.innerText = 'Network or server error. Please try again.';
                alertBox.classList.remove('d-none');
            });
        });
    }
</script>
@endpush
