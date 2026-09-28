@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mt-4">Refund Request Details</h2>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Refund #{{ $refund->id }}</h5>
                    <span class="badge {{ $refund->status == 'pending' ? 'bg-warning' : ($refund->status == 'approved' ? 'bg-success' : 'bg-danger') }}">
                        {{ ucfirst($refund->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Ticket ID</h6>
                            <p>{{ $refund->ticket->ticket_id }}</p>
                            
                            <h6>Event</h6>
                            <p>{{ $refund->ticket->event->title }}</p>
                            
                            <h6>Customer</h6>
                            <p>{{ $refund->user ? $refund->user->name : 'Guest' }} ({{ $refund->user ? $refund->user->email : 'N/A' }})</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Refund Method</h6>
                            <p>{{ $refund->refund_method }}</p>
                            
                            <h6>Refund Amount</h6>
                            <p>${{ number_format($refund->refund_amount, 2) }}</p>
                            
                            <h6>Requested At</h6>
                            <p>{{ $refund->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Refund Details</h6>
                    <p><strong>Reason:</strong> {{ $refund->reason }}</p>
                    @if($refund->explanation)
                    <p><strong>Explanation:</strong> {{ $refund->explanation }}</p>
                    @endif
                    <p><strong>Reference Number:</strong> {{ $refund->reference_number }}</p>
                    <p><strong>Expected Processing Days:</strong> {{ $refund->expected_processing_days }} business days</p>
                    @if($refund->policy_version_id)
                    <p><strong>Policy Version:</strong> {{ $refund->policy_version_id }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @if($refund->status === 'pending')
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Admin Actions</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100" onclick="approveRefund({{ $refund->id }})">
                        Approve Refund
                    </button>
                    <button class="btn btn-danger w-100" onclick="rejectRefund({{ $refund->id }})">
                        Reject Refund
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="row mt-3">
        <div class="col-md-12">
            <a href="/admin/refunds" class="btn btn-link">← Back to Refund List</a>
        </div>
    </div>
</div>

<script>
    function approveRefund(id) {
        if (confirm('Are you sure you want to approve this refund?')) {
            window.location.href = '/admin/refunds/' + id + '/approve';
        }
    }
    
    function rejectRefund(id) {
        var reason = prompt('Enter rejection reason:');
        if (reason !== null && reason.trim() !== '') {
            window.location.href = '/admin/refunds/' + id + '/reject?admin_notes=' + encodeURIComponent(reason);
        }
    }
</script>
</div>
OUTEREOF