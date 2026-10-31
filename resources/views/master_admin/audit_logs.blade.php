@extends('layouts.master_admin')

@section('title', 'Audit Activity Logs')

@section('content')
<div class="content-header pt-3 pb-2">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="m-0 text-dark font-weight-bold" style="font-size: 1.5rem;">Platform Audit Activity Logs</h1>
                <p class="text-muted text-xs mb-0 mt-1">
                    Complete immutable chronological trail of administrative actions, account provisioning, password resets, and user logins.
                </p>
            </div>
            <div>
                <a href="{{ route('master-admin.dashboard') }}" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-master shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-sm">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Timestamp</th>
                                <th>Event</th>
                                <th>Performed By</th>
                                <th>Business Tenant</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="text-muted">{{ $log->id }}</td>
                                    <td class="text-xs text-muted">
                                        {{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') : '—' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">
                                            {{ ucwords(str_replace('_', ' ', $log->event)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                        <small class="text-muted">{{ $log->user?->email ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        {{ $log->business?->name ?? ($log->user?->business?->name ?? 'Global / Master') }}
                                    </td>
                                    <td>
                                        <code>{{ $log->ip_address ?: '127.0.0.1' }}</code>
                                    </td>
                                    <td class="text-xs text-muted" style="max-width: 250px;">
                                        @if ($log->new_values)
                                            <code>{{ json_encode($log->new_values) }}</code>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No audit activity logs recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 d-flex justify-content-between align-items-center bg-light border-top">
                    <span class="text-xs text-muted">
                        Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} activity records
                    </span>
                    <div>
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
