@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Purchases</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print PO</button>
</div>

<div class="card shadow-sm border-0 p-4">
    <div class="row border-bottom pb-3 mb-3">
        <div class="col-sm-7">
            <h4 class="font-weight-bold text-dark mb-1">{{ $purchase->branch->business->name }}</h4>
            <div class="text-sm text-muted">Delivered to: <strong>{{ $purchase->branch->name }}</strong></div>
            <div class="text-sm text-muted">{{ $purchase->branch->address_line1 }}, {{ $purchase->branch->city }}</div>
            <div class="text-sm mt-1">
                <span class="badge badge-light border">GSTIN: {{ $purchase->branch->gstin ?: $purchase->branch->business->gstin }}</span>
            </div>
        </div>
        <div class="col-sm-5 text-sm-right mt-3 mt-sm-0">
            <h4 class="font-weight-bold text-primary mb-1">PURCHASE ORDER</h4>
            <div class="text-sm"><strong>PO Number:</strong> <code>{{ $purchase->po_number }}</code></div>
            <div class="text-sm"><strong>Supplier Inv #:</strong> {{ $purchase->supplier_invoice_number ?: '---' }}</div>
            <div class="text-sm"><strong>Order Date:</strong> {{ $purchase->purchase_date->format('d-m-Y') }}</div>
            <div class="text-sm"><strong>Status:</strong> <span class="badge badge-success">{{ strtoupper($purchase->status) }}</span></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded">
                <h6 class="text-xs font-weight-bold text-uppercase text-muted mb-2">Supplier Details</h6>
                <h6 class="font-weight-bold text-dark mb-1">{{ $purchase->supplier->name }}</h6>
                <div class="text-sm text-muted">{{ $purchase->supplier->company_name }}</div>
                <div class="text-sm text-muted">{{ $purchase->supplier->address }}</div>
                <div class="text-sm font-weight-bold text-primary mt-1">GSTIN: {{ $purchase->supplier->gstin ?: 'Unregistered' }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm mb-0">
            <thead class="bg-light text-center text-xs text-uppercase">
                <tr>
                    <th>#</th>
                    <th class="text-left">Product Name</th>
                    <th>Qty Ordered</th>
                    <th>Qty Received</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Taxable</th>
                    <th class="text-right">GST Rate</th>
                    <th class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $idx => $item)
                    <tr class="text-sm">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-weight-medium">{{ $item->product->name }}</td>
                        <td class="text-center">{{ number_format($item->quantity_ordered, 2) }} {{ $item->unit?->code }}</td>
                        <td class="text-center font-weight-bold text-success">{{ number_format($item->quantity_received, 2) }}</td>
                        <td class="text-right">₹ {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-right">₹ {{ number_format($item->taxable_amount, 2) }}</td>
                        <td class="text-right">{{ $item->gst_rate }}%</td>
                        <td class="text-right font-weight-bold">₹ {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="7" class="text-right font-weight-bold">Total Purchase Amount:</th>
                    <th class="text-right font-weight-bold text-success" style="font-size: 1.05rem;">₹ {{ number_format($purchase->total_amount, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="7" class="text-right">Amount Paid:</th>
                    <th class="text-right font-weight-bold">₹ {{ number_format($purchase->paid_amount, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="7" class="text-right text-danger">Balance Due to Supplier:</th>
                    <th class="text-right font-weight-bold text-danger">₹ {{ number_format($purchase->balance_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
