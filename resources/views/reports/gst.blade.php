@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i>Indian GST Outward Supplies Summary (GSTR-1 Format)</h4>
        <span class="text-muted text-sm">Compliant reporting for GSTR-1 return filing: Table 4 (B2B), Table 5/7 (B2C), and Table 12 (HSN Summary).</span>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print GSTR-1</button>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-3 no-print">
    <div class="card-body p-3">
        <form action="{{ route('reports.gst') }}" method="GET" class="row">
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="text-xs font-weight-bold text-uppercase text-muted">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="text-xs font-weight-bold text-uppercase text-muted">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-4 text-right align-self-end">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter mr-1"></i> Filter Return Period</button>
                <a href="{{ route('reports.gst') }}" class="btn btn-outline-secondary btn-sm ml-1">Current Month</a>
            </div>
        </form>
    </div>
</div>

<!-- SECTION 1: TABLE 4 - B2B TAXABLE SUPPLIES -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0">
            <span class="badge badge-primary mr-1">Table 4</span> 4A, 4B - Supplies to Registered Taxable Persons (B2B)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Recipient GSTIN</th>
                        <th>Legal / Trade Name</th>
                        <th>Invoice No</th>
                        <th>Invoice Date</th>
                        <th>Place of Supply</th>
                        <th class="text-right">Taxable Value (₹)</th>
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                        <th class="text-right">IGST (₹)</th>
                        <th class="text-right">Invoice Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($b2bSales as $s)
                        <tr>
                            <td><code>{{ $s->customer->gstin }}</code></td>
                            <td class="font-weight-medium text-dark">{{ $s->customer->name }}</td>
                            <td><a href="{{ route('sales.show', $s->id) }}" class="font-weight-bold text-primary">{{ $s->invoice_number }}</a></td>
                            <td class="text-muted">{{ $s->invoice_date->format('d-m-Y') }}</td>
                            <td><small class="badge badge-light border">{{ $s->place_of_supply }}</small></td>
                            <td class="text-right font-weight-medium">₹ {{ number_format($s->taxable_amount, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($s->cgst_amount, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($s->sgst_amount, 2) }}</td>
                            <td class="text-right text-info font-weight-medium">₹ {{ number_format($s->igst_amount, 2) }}</td>
                            <td class="text-right font-weight-bold">₹ {{ number_format($s->final_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No B2B invoices recorded in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($b2bSales->count() > 0)
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="5" class="text-right font-weight-bold">Total B2B Outward Supplies:</th>
                        <th class="text-right font-weight-bold">₹ {{ number_format($b2bSales->sum('taxable_amount'), 2) }}</th>
                        <th class="text-right font-weight-bold">₹ {{ number_format($b2bSales->sum('cgst_amount'), 2) }}</th>
                        <th class="text-right font-weight-bold">₹ {{ number_format($b2bSales->sum('sgst_amount'), 2) }}</th>
                        <th class="text-right font-weight-bold">₹ {{ number_format($b2bSales->sum('igst_amount'), 2) }}</th>
                        <th class="text-right font-weight-bold text-success">₹ {{ number_format($b2bSales->sum('final_amount'), 2) }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<!-- SECTION 2: TABLE 7 - B2C CONSUMER SUPPLIES -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0">
            <span class="badge badge-info mr-1">Table 7</span> 7B - Supplies to Consumers & Unregistered Persons (B2C)
        </h6>
    </div>
    <div class="card-body p-3">
        <div class="row">
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">Invoices Count</span>
                <h5 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($b2cSales->total_invoices ?? 0) }} Invoices</h5>
            </div>
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">Taxable Value</span>
                <h5 class="font-weight-bold text-dark mb-0 mt-1">₹ {{ number_format($b2cSales->taxable ?? 0, 2) }}</h5>
            </div>
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">CGST</span>
                <h5 class="font-weight-bold text-info mb-0 mt-1">₹ {{ number_format($b2cSales->cgst ?? 0, 2) }}</h5>
            </div>
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">SGST</span>
                <h5 class="font-weight-bold text-info mb-0 mt-1">₹ {{ number_format($b2cSales->sgst ?? 0, 2) }}</h5>
            </div>
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">IGST</span>
                <h5 class="font-weight-bold text-info mb-0 mt-1">₹ {{ number_format($b2cSales->igst ?? 0, 2) }}</h5>
            </div>
            <div class="col-md-2 mb-2">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">Total B2C Value</span>
                <h5 class="font-weight-bold text-success mb-0 mt-1">₹ {{ number_format($b2cSales->total ?? 0, 2) }}</h5>
            </div>
        </div>
    </div>
</div>

<!-- SECTION 3: TABLE 12 - HSN SUMMARY OF OUTWARD SUPPLIES -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="font-weight-bold text-dark mb-0">
            <span class="badge badge-success mr-1">Table 12</span> HSN-wise Summary of Outward Supplies
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>HSN Code</th>
                        <th>Description</th>
                        <th class="text-center">UQC</th>
                        <th class="text-center">Total Quantity</th>
                        <th class="text-right">Total Taxable Value (₹)</th>
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                        <th class="text-right">IGST (₹)</th>
                        <th class="text-right">Total Value (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($hsnSummary as $row)
                        <tr>
                            <td><code>{{ $row->hsn_code ?: 'N/A' }}</code></td>
                            <td class="font-weight-medium text-dark">{{ $row->product_name }}</td>
                            <td class="text-center"><span class="badge badge-light border">{{ $row->unit_code }}</span></td>
                            <td class="text-center font-weight-semibold">{{ number_format($row->total_qty, 2) }}</td>
                            <td class="text-right font-weight-medium">₹ {{ number_format($row->total_taxable, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($row->total_cgst, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($row->total_sgst, 2) }}</td>
                            <td class="text-right text-muted">₹ {{ number_format($row->total_igst, 2) }}</td>
                            <td class="text-right font-weight-bold text-dark">₹ {{ number_format($row->total_value, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No HSN data available for this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
