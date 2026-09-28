@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2 class="mt-4">Refund Requests</h2>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <select name="status" id="statusFilter" class="form-select">
                <option value="all">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" onclick="refreshTable()">Refresh</button>
        </div>
    </div>
    
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>#</th>
                <th>Ticket ID</th>
                <th>User</th>
                <th>Event</th>
                <th>Refund Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Requested At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="refundTableBody">
            @foreach ($refunds as $refund)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $refund->ticket->ticket_id }}</td>
                <td>{{ $refund->user ? $refund->user->name : 'Guest' }}</td>
                <td>{{ $refund->ticket->event->title }}</td>
                <td>{{ $refund->refund_method }}</td>
                <td>${{ number_format($refund->refund_amount, 2) }}</td>
                <td>
                    <span class="badge {{ $refund->status == 'pending' ? 'bg-warning' : ($refund->status == 'approved' ? 'bg-success' : 'bg-danger') }}">
                        {{ ucfirst($refund->status) }}
                    </span>
                </td>
                <td>{{ $refund->created_at->format('M d, Y H:i') }}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-details" data-id="{{ $refund->id }}">Details</button>
                        @can('refund approve')
                        <button class="btn btn-outline-success btn-approve" data-id="{{ $refund->id }}">Approve</button>
                        @endcan
                        @can('refund reject')
                        <button class="btn btn-outline-danger btn-reject" data-id="{{ $refund->id }}">Reject</button>
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    {{ $refunds->links('vendor.pagination.bootstrap-4') }}
</div>

<script>
    // Status filter change
    $('#statusFilter').on('change', function() {
        window.location.href = '?status=' + $(this).val();
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#refundTableBody tr').filter(function() {
            return $(this).text().toLowerCase().indexOf(value) > -1;
        });
    });

    // Refresh table
    function refreshTable() {
        window.location.reload();
    }

    // Details button
    $('.btn-details').on('click', function() {
        window.location.href = '/admin/refunds/' + $(this).data('id');
    });

    // Approve button
    $('.btn-approve').on('click', function() {
        if (confirm('Are you sure you want to approve this refund?')) {
            $.ajax({
                url: '/admin/refunds/' + $(this).data('id') + '/approve',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    alert('Refund approved successfully');
                    refreshTable();
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        }
    });

    // Reject button
    $('.btn-reject').on('click', function() {
        var reason = prompt('Enter rejection reason:');
        if (reason !== null && reason.trim() !== '') {
            $.ajax({
                url: '/admin/refunds/' + $(this).data('id') + '/reject',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { admin_notes: reason },
                success: function(response) {
                    alert('Refund rejected successfully');
                    refreshTable();
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON.message);
                }
            });
        }
    });
</script>
