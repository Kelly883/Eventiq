@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mt-4">Approve Refund Request</h2>
    
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
                    
                    <h6>Approval Details</h6>
                    <div class="mb-3">
                        <label for="approved_amount">Approved Amount (leave blank for full amount):</label>
                        <input type="number" step="0.01" id="approved_amount" class="form-control"
                            value="{{ $refund->refund_amount }}">
                    </div>
                    <div class="mb-3">
                        <label for="admin_notes">Admin Notes:</label>
                        <textarea class="form-control" id="admin_notes" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <button class="btn btn-primary w-100" onclick="submitApproval()">
                Approve Refund
            </button>
            <a href="/admin/refunds/{{ $refund->id }}" class="btn btn-link">Cancel</a>
        </div>
    </div>
</div>

<script>
    function submitApproval() {
        var amount = $('#approved_amount').val();
        var notes = $('#admin_notes').val();
        
        $.ajax({
            url: '/admin/refunds/{{ $refund->id }}/approve',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                approved_amount: amount || null,
                admin_notes: notes
            },
            success: function(response) {
                alert('Refund approved successfully');
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