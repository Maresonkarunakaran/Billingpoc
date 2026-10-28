@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Transfers</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print Delivery Challan</button>
</div>

<div class="card shadow-sm border-0 p-4">
    <div class="row border-bottom pb-3 mb-3">
        <div class="col-sm-7">
            <h4 class="font-weight-bold text-dark mb-1">{{ $transfer->business->name }}</h4>
            <div class="text-sm font-weight-bold text-uppercase text-primary">INTERNAL STOCK TRANSFER DELIVERY CHALLAN</div>
            <div class="text-sm text-muted">Rule 55 CGST Rules Delivery Challan for Inter-Branch Movement</div>
        </div>
        <div class="col-sm-5 text-sm-right mt-3 mt-sm-0">
            <h4 class="font-weight-bold text-dark mb-1">CHALLAN #{{ $transfer->transfer_number }}</h4>
            <div class="text-sm"><strong>Date:</strong> {{ $transfer->transfer_date->format('d-m-Y') }}</div>
            <div class="text-sm"><strong>Status:</strong> <span class="badge badge-success">{{ strtoupper($transfer->status) }}</span></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-sm-6">
            <div class="p-3 bg-light rounded">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">Origin (Dispatched From)</span>
                <h6 class="font-weight-bold text-dark mb-1">{{ $transfer->sourceBranch->name }}</h6>
                <div class="text-sm text-muted">{{ $transfer->sourceBranch->address_line1 }}, {{ $transfer->sourceBranch->city }}</div>
                <div class="text-sm text-muted">GSTIN: {{ $transfer->sourceBranch->gstin }}</div>
            </div>
        </div>
        <div class="col-sm-6 mt-3 mt-sm-0">
            <div class="p-3 bg-light rounded">
                <span class="text-xs font-weight-bold text-uppercase text-muted d-block">Destination (Consigned To)</span>
                <h6 class="font-weight-bold text-dark mb-1">{{ $transfer->destinationBranch->name }}</h6>
                <div class="text-sm text-muted">{{ $transfer->destinationBranch->address_line1 }}, {{ $transfer->destinationBranch->city }}</div>
                <div class="text-sm text-muted">GSTIN: {{ $transfer->destinationBranch->gstin }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm mb-0">
            <thead class="bg-light text-center text-xs text-uppercase">
                <tr>
                    <th>#</th>
                    <th class="text-left">Product Description</th>
                    <th>HSN</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th class="text-right">Transfer Cost Value (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->items as $idx => $item)
                    <tr class="text-sm">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-weight-medium">{{ $item->product->name }}</td>
                        <td class="text-center"><code>{{ $item->product->hsn_code ?? '---' }}</code></td>
                        <td class="text-center font-weight-bold">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center text-muted">{{ $item->product->baseUnit->code }}</td>
                        <td class="text-right font-weight-bold">₹ {{ number_format($item->total_cost, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="5" class="text-right font-weight-bold">Total Transferred Inventory Valuation:</th>
                    <th class="text-right font-weight-bold text-success" style="font-size: 1.05rem;">₹ {{ number_format($transfer->total_cost, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($transfer->notes)
        <div class="p-2 bg-light rounded text-xs text-muted mb-4">
            <strong>Dispatch Notes:</strong> {{ $transfer->notes }}
        </div>
    @endif

    <div class="row mt-5 pt-3 border-top">
        <div class="col-6">
            <span class="text-xs font-weight-bold text-muted text-uppercase d-block">Dispatched by</span>
            <div style="height: 35px;"></div>
            <span class="text-xs font-weight-bold text-dark border-top pt-1 d-inline-block">Warehouse In-charge Signature</span>
        </div>
        <div class="col-6 text-right">
            <span class="text-xs font-weight-bold text-muted text-uppercase d-block">Received by</span>
            <div style="height: 35px;"></div>
            <span class="text-xs font-weight-bold text-dark border-top pt-1 d-inline-block">Store Receiver Signature</span>
        </div>
    </div>
</div>
@endsection
