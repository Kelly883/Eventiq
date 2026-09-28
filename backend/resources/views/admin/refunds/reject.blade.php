@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mt-4">Reject Refund Request</h2>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Refund #{{ $refund->id }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>Customer:</strong> {{ $refund->user ? $refund->user->name : 'Guest' }} ({{ $refund->user ? $refund->user->email : 'N/A' }})</p>
                    <p><strong>Ticket ID:</strong> {{ $refund->ticket->ticket_id }}</p>
                    <p><strong>Event:</strong> {{ $refund->ticket->event->title }}</p>
                    <p><strong>Refund Amount:</strong> ${{ number_format($refund->refund_amount, 2) }}</p>
                    <p><strong>Reason:</strong> {{ $refund->reason }}</p>
                    
                    <hr>
                    
                    <h6>Rejection Details</h6>
                    <div class="mb-3">
                        <label for="admin_notes">Rejection Reason:</label>
                        <textarea class="form-control" id="admin_notes" rows="3" required></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-danger w-100" onclick="submitRejection()">Reject Refund</button>
            <a href="/admin/refunds/{{ $refund->id }}" class="btn btn-link">Cancel</a>
        </div>
    </div>
</div>

<script>
    function submitRejection() {
        var reason = $('#admin_notes').val();
        
        if (!reason.trim()) {
            alert('Please enter a rejection reason');
            return;
        }
        
        $.ajax({
            url: '/admin/refunds/{{ $refund->id }}/reject',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { admin_notes: reason },
            success: function(response) {
                alert('Refund rejected successfully');
                window.location.href = '/admin/refunds/{{ $refund->id }}';
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseJSON.message);
            }
        });
    }
</script>
</div>
OUTEREOF