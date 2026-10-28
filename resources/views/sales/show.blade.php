@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Sales</a>
    <div>
        <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="btn btn-secondary btn-sm mr-1"><i class="fas fa-receipt mr-1"></i> Thermal Receipt</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm mr-1"><i class="fas fa-print mr-1"></i> Print Tax Invoice (A4)</button>
        @if($sale->invoice_status !== 'returned')
            <a href="{{ route('sales.return', $sale->id) }}" class="btn btn-danger btn-sm"><i class="fas fa-undo mr-1"></i> Issue Return / Credit Note</a>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0 p-4" id="printableInvoice">
    <!-- Invoice Header -->
    <div class="row border-bottom pb-3 mb-3">
        <div class="col-sm-7">
            <h4 class="font-weight-bold text-dark mb-1">{{ $sale->branch->business->legal_name ?? $sale->branch->business->name }}</h4>
            <div class="text-sm text-muted">Branch: <strong>{{ $sale->branch->name }}</strong></div>
            <div class="text-sm text-muted">{{ $sale->branch->address_line1 }}, {{ $sale->branch->city }} - {{ $sale->branch->pincode }}</div>
            <div class="text-sm text-muted">Phone: {{ $sale->branch->phone }} | Email: {{ $sale->branch->email }}</div>
            <div class="text-sm mt-1">
                <span class="badge badge-primary px-2 py-1">GSTIN: {{ $sale->branch->gstin ?: $sale->branch->business->gstin }}</span>
                <span class="badge badge-light border px-2 py-1 ml-1">State: {{ $sale->branch->state_code }} - {{ $sale->branch->state_name }}</span>
            </div>
        </div>
        <div class="col-sm-5 text-sm-right mt-3 mt-sm-0">
            <h3 class="font-weight-bold text-uppercase text-primary mb-1">TAX INVOICE</h3>
            <div class="text-sm"><strong>Invoice No:</strong> <code>{{ $sale->invoice_number }}</code></div>
            <div class="text-sm"><strong>Invoice Date:</strong> {{ $sale->invoice_date->format('d-m-Y') }}</div>
            <div class="text-sm"><strong>Place of Supply:</strong> {{ $sale->place_of_supply }}</div>
            <div class="text-sm"><strong>Supply Type:</strong> {{ $sale->is_interstate ? 'Inter-state (IGST)' : 'Intra-state (CGST + SGST)' }}</div>
            <div class="text-sm"><strong>Reverse Charge:</strong> No</div>
        </div>
    </div>

    <!-- Recipient / Biller Details -->
    <div class="row mb-4">
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded h-100">
                <h6 class="text-xs font-weight-bold text-uppercase text-muted mb-2">Billed To (Recipient Details)</h6>
                @if($sale->customer)
                    <h6 class="font-weight-bold text-dark mb-1">{{ $sale->customer->name }}</h6>
                    @if($sale->customer->address)
                        <div class="text-sm text-muted">{{ $sale->customer->address }}</div>
                    @endif
                    <div class="text-sm text-muted">Phone: {{ $sale->customer->phone ?: '---' }}</div>
                    @if($sale->customer->gstin)
                        <div class="text-sm mt-1 font-weight-bold text-primary">GSTIN: {{ $sale->customer->gstin }}</div>
                    @endif
                    <div class="text-sm text-muted">State: {{ $sale->customer->state_code }} - {{ $sale->customer->state_name }}</div>
                @else
                    <h6 class="font-weight-bold text-dark mb-1">Cash Retail Customer</h6>
                    <div class="text-sm text-muted">Counter Sale (Unregistered B2C)</div>
                @endif
            </div>
        </div>
        <div class="col-sm-6 mt-3 mt-sm-0">
            <div class="p-3 bg-light rounded h-100">
                <h6 class="text-xs font-weight-bold text-uppercase text-muted mb-2">Payment & Billing Summary</h6>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span>Pricing Mode:</span>
                    <span class="font-weight-bold text-uppercase">{{ $sale->pricing_type }}</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span>Billing Shift:</span>
                    <span>#{{ $sale->shift_id ?? 'Direct' }} (Cashier: {{ $sale->user->name }})</span>
                </div>
                <div class="d-flex justify-content-between text-sm mb-1">
                    <span>Payment Status:</span>
                    <span class="badge {{ $sale->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($sale->payment_status) }}</span>
                </div>
                <div class="d-flex justify-content-between text-sm">
                    <span>Balance Outstanding:</span>
                    <span class="font-weight-bold text-danger">₹ {{ number_format($sale->balance_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm mb-0">
            <thead class="bg-light">
                <tr class="text-center" style="font-size: 0.8rem;">
                    <th style="width: 35px;">#</th>
                    <th class="text-left">Product Description</th>
                    <th>HSN</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th class="text-right">Rate (₹)</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Taxable (₹)</th>
                    @if($sale->is_interstate)
                        <th class="text-right">IGST (₹)</th>
                    @else
                        <th class="text-right">CGST (₹)</th>
                        <th class="text-right">SGST (₹)</th>
                    @endif
                    <th class="text-right">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $idx => $item)
                    <tr style="font-size: 0.86rem;">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-weight-medium">{{ $item->product->name }}</td>
                        <td class="text-center"><code>{{ $item->hsn_code ?? '---' }}</code></td>
                        <td class="text-center font-weight-semibold">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center text-muted">{{ $item->unit->code }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->discount_amount, 2) }}</td>
                        <td class="text-right font-weight-medium">{{ number_format($item->taxable_amount, 2) }}</td>
                        @if($sale->is_interstate)
                            <td class="text-right text-xs">
                                <div>{{ number_format($item->igst_amount, 2) }}</div>
                                <span class="text-muted">({{ $item->igst_rate }}%)</span>
                            </td>
                        @else
                            <td class="text-right text-xs">
                                <div>{{ number_format($item->cgst_amount, 2) }}</div>
                                <span class="text-muted">({{ $item->cgst_rate }}%)</span>
                            </td>
                            <td class="text-right text-xs">
                                <div>{{ number_format($item->sgst_amount, 2) }}</div>
                                <span class="text-muted">({{ $item->sgst_rate }}%)</span>
                            </td>
                        @endif
                        <td class="text-right font-weight-bold">{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals and Tax Breakdown -->
    <div class="row">
        <!-- HSN Summary Table -->
        <div class="col-md-7">
            <h6 class="text-xs font-weight-bold text-uppercase text-muted mb-2">HSN / SAC Tax Analysis</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-center mb-0" style="font-size: 0.78rem;">
                    <thead class="bg-light">
                        <tr>
                            <th>HSN/SAC</th>
                            <th>Taxable</th>
                            @if($sale->is_interstate)
                                <th>IGST</th>
                            @else
                                <th>CGST</th>
                                <th>SGST</th>
                            @endif
                            <th>Total Tax</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hsnGroups = $sale->items->groupBy('hsn_code');
                        @endphp
                        @foreach($hsnGroups as $hsn => $group)
                            <tr>
                                <td><code>{{ $hsn ?: 'N/A' }}</code></td>
                                <td>₹ {{ number_format($group->sum('taxable_amount'), 2) }}</td>
                                @if($sale->is_interstate)
                                    <td>₹ {{ number_format($group->sum('igst_amount'), 2) }}</td>
                                @else
                                    <td>₹ {{ number_format($group->sum('cgst_amount'), 2) }}</td>
                                    <td>₹ {{ number_format($group->sum('sgst_amount'), 2) }}</td>
                                @endif
                                <td class="font-weight-bold">₹ {{ number_format($group->sum('cgst_amount') + $group->sum('sgst_amount') + $group->sum('igst_amount'), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            @if($sale->notes)
                <div class="mt-3 p-2 bg-light rounded text-xs text-muted">
                    <strong>Invoice Note:</strong> {{ $sale->notes }}
                </div>
            @endif
        </div>

        <!-- Grand Totals Block -->
        <div class="col-md-5">
            <table class="table table-sm table-borderless text-sm mb-0">
                <tr>
                    <td class="text-muted">Subtotal (Gross Line Value):</td>
                    <td class="text-right font-weight-medium">₹ {{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                @if($sale->discount_amount > 0)
                <tr>
                    <td class="text-danger">Invoice Discount:</td>
                    <td class="text-right text-danger">- ₹ {{ number_format($sale->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="text-muted">Total Taxable Value:</td>
                    <td class="text-right font-weight-medium">₹ {{ number_format($sale->taxable_amount, 2) }}</td>
                </tr>
                @if($sale->is_interstate)
                    <tr>
                        <td class="text-muted">IGST Total:</td>
                        <td class="text-right font-weight-medium text-info">₹ {{ number_format($sale->igst_amount, 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="text-muted">Central GST (CGST):</td>
                        <td class="text-right font-weight-medium text-info">₹ {{ number_format($sale->cgst_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">State GST (SGST):</td>
                        <td class="text-right font-weight-medium text-info">₹ {{ number_format($sale->sgst_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="text-muted">Round Off:</td>
                    <td class="text-right">{{ $sale->round_off >= 0 ? '+ ' : '- ' }}₹ {{ number_format(abs($sale->round_off), 2) }}</td>
                </tr>
                <tr class="border-top border-bottom">
                    <th class="font-weight-bold" style="font-size: 1.1rem;">Invoice Total:</th>
                    <th class="text-right font-weight-bold text-success" style="font-size: 1.1rem;">₹ {{ number_format($sale->final_amount, 2) }}</th>
                </tr>
                <tr>
                    <td>Total Paid:</td>
                    <td class="text-right font-weight-bold text-dark">₹ {{ number_format($sale->paid_amount, 2) }}</td>
                </tr>
                @if($sale->balance_amount > 0)
                <tr>
                    <td class="text-danger font-weight-bold">Balance Dues:</td>
                    <td class="text-right font-weight-bold text-danger">₹ {{ number_format($sale->balance_amount, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Signatures & Declaration -->
    <div class="row mt-5 pt-3 border-top">
        <div class="col-8">
            <span class="text-xs font-weight-bold text-muted text-uppercase d-block">Declaration</span>
            <p class="text-xs text-muted mb-0">
                {{ $sale->branch->business->getSetting('gst_declaration', 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.') }}
            </p>
        </div>
        <div class="col-4 text-right">
            <span class="text-xs font-weight-bold text-muted text-uppercase d-block">For {{ $sale->branch->business->name }}</span>
            <div style="height: 45px;"></div>
            <span class="text-xs font-weight-bold text-dark border-top pt-1 d-inline-block">Authorised Signatory</span>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print, .main-header, .main-sidebar, .main-footer { display: none !important; }
        .content-wrapper { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        #printableInvoice { border: none !important; box-shadow: none !important; padding: 0 !important; }
    }
</style>
@endpush
