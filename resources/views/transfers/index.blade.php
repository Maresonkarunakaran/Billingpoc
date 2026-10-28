@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-truck-moving text-info mr-2"></i>Branch-to-Branch Stock Transfers</h4>
        <span class="text-muted text-sm">Move stock seamlessly between central warehouses and retail shops with atomic row-locking.</span>
    </div>
    <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm px-3">
        <i class="fas fa-plus mr-1"></i> New Transfer Request
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>Date</th>
                        <th>Source Branch (Sender)</th>
                        <th>Destination Branch (Receiver)</th>
                        <th class="text-center">Items Count</th>
                        <th class="text-right">Total Transferred Cost</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $t)
                        <tr>
                            <td>
                                <a href="{{ route('transfers.show', $t->id) }}" class="font-weight-bold text-primary">
                                    {{ $t->transfer_number }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $t->transfer_date->format('d M Y') }}</td>
                            <td><span class="badge badge-warning px-2 py-1"><i class="fas fa-arrow-up mr-1"></i> {{ $t->sourceBranch->name }}</span></td>
                            <td><span class="badge badge-success px-2 py-1"><i class="fas fa-arrow-down mr-1"></i> {{ $t->destinationBranch->name }}</span></td>
                            <td class="text-center font-weight-bold">{{ $t->items->count() }} Items</td>
                            <td class="text-right font-weight-bold">₹ {{ number_format($t->total_cost, 2) }}</td>
                            <td class="text-center">
                                <span class="badge badge-success px-2 py-1">{{ strtoupper($t->status) }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('transfers.show', $t->id) }}" class="btn btn-xs btn-outline-primary" title="View Delivery Challan"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">No stock transfers recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transfers->hasPages())
        <div class="card-footer bg-white py-2 border-top">
            {{ $transfers->links() }}
        </div>
    @endif
</div>
@endsection
