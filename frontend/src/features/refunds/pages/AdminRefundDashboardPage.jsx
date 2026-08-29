import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import Skeleton from '../../../components/Skeleton';
import { LoadingSpinner, ErrorBoundary } from '../../common';
import { REFUND_STATUSES } from '../../../features/refunds/constants';
import { formatRefundStatus, calculateRefundAmount } from '../../../features/refunds/utils';
import { REFUND_STATUSES as STATUS_ENUM } from '../../../features/refunds/constants';
import { useRefunds } from '../../../features/refunds/hooks';
import { RefundFilterBar, RefundMetricCard } from '../components';
import RefundTable from '../components/RefundTable';

const AdminRefundDashboardPage = () => {
  const { user, loading, checkAdminAccess } = useAuthContext();
  const navigate = useNavigate();
  const location = useLocation();

  const [metrics, setMetrics] = useState({
    totalPending: 0,
    totalPendingAmount: 0,
    approvalRate: 0,
    averageProcessingTime: 0,
  });

  const [refunds, setRefunds] = useState([]);
  const [filters, setFilters] = useState({
    status: '',
    event: '',
    dateRange: '',
    amount: '',
    reason: '',
    searchTicket: '',
    searchEmail: '',
  });

  const [columns, setColumns] = useState([
    { accessorKey: 'ticketId', header: 'Ticket ID', size: 120 },
    { accessorKey: 'eventName', header: 'Event Name', size: 200 },
    { accessorKey: 'buyerEmail', header: 'Buyer Email', size: 180 },
    { accessorKey: 'refundAmount', header: 'Refund Amount', size: 100 },
    { accessorKey: 'reason', header: 'Reason', size: 200 },
    { accessorKey: 'status', header: 'Status', size: 100 },
    { accessorKey: 'submissionDate', header: 'Submission Date', size: 140 },
    { accessorKey: 'actions', header: 'Actions', size: 140 },
  ]);

  // Load admin refund data
  useEffect(() => {
    if (!checkAdminAccess()) {
      navigate('/admin/refunds/dashboard'); // Stay on page
      return;
    }

    // Fetch admin refund data
    // In production, this would be:
    // api.get('/api/admin/refunds/list', { params: filters })
    
    // Mock data for now
    const mockRefunds = [
      {
        id: 'R001',
        ticketId: 'T1001',
        eventName: 'Tech Conference 2024',
        buyerEmail: 'user1@example.com',
        refundAmount: 150,
        reason: 'Event cancelled',
        status: 'pending',
        submissionDate: new Date(),
      },
      {
        id: 'R002',
        ticketId: 'T1002',
        eventName: 'Music Festival',
        buyerEmail: 'user2@example.com',
        refundAmount: 298,
        reason: 'Cannot attend',
        status: 'approved',
        submissionDate: new Date(Date.now() - 86400000),
        processedAt: new Date(Date.now() - 43200000),
      },
      {
        id: 'R003',
        ticketId: 'T1003',
        eventName: 'Workshop Series',
        buyerEmail: 'user3@example.com',
        refundAmount: 75,
        reason: 'Duplicate purchase',
        status: 'rejected',
        submissionDate: new Date(Date.now() - 172800000),
        processedAt: null,
      },
      {
        id: 'R004',
        ticketId: 'T1004',
        eventName: 'Gala Dinner',
        buyerEmail: 'user4@example.com',
        refundAmount: 500,
        reason: 'Event cancelled',
        status: 'processing',
        submissionDate: new Date(Date.now() - 259200000),
        processedAt: null,
      },
    ];

    setRefunds(mockRefunds);

    // Calculate metrics
    const pending = mockRefunds.filter(r => r.status === 'pending');
    const pendingAmount = pending.reduce((sum, r) => sum + r.refundAmount, 0);
    const total = mockRefunds.length;
    const approved = mockRefunds.filter(r => r.status === 'approved').length;
    const approvalRate = total > 0 ? (approved / total * 100).toFixed(1) : 0;
    
    // Average processing time (for approved refunds)
    const approvedRefunds = mockRefunds.filter(r => r.status === 'approved' && r.processedAt);
    const avgTime = approvedRefunds.length > 0 ? 
      (approvedRefunds.reduce((sum, r) => {
        return sum + (r.processedAt - r.submissionDate) / 86400000; // days
      }, 0) / approvedRefunds.length).toFixed(1) : 'N/A';
    
    setMetrics({
      totalPending: pending.length,
      totalPendingAmount: pendingAmount,
      approvalRate: parseFloat(approvalRate),
      averageProcessingTime: avgTime,
    });
  }, [checkAdminAccess, navigate]);

  // Handle status update
  const handleStatusUpdate = async (refundId, newStatus, reason) => {
    try {
      if (newStatus === 'approve') {
        await api.post(`/api/admin/refunds/approve`, { refundId });
      } else if (newStatus === 'reject') {
        await api.post(`/api/admin/refunds/reject`, { refundId, reason });
      }
      showToast('Success', 'Refund status updated.', 'success');
      // Refetch data
      // In production would refetch
    } catch (err) {
      showToast('Error', 'Failed to update refund status.', 'error');
    }
  };

  // Handle bulk operations
  const handleBulkUpdate = async (status) => {
    const selected = refunds.filter(r => r._selected);
    if (selected.length === 0) return;
    
    try {
      if (status === 'approve') {
        await api.post('/api/admin/refunds/bulk-approve', { refundIds: selected.map(r => r.id) });
      } else if (status === 'reject') {
        await api.post('/api/admin/refunds/bulk-reject', { refundIds: selected.map(r => r.id), reason: 'Bulk rejection' });
      }
      showToast('Success', `${selected.length} refunds updated.`, 'success');
    } catch (err) {
      showToast('Error', 'Failed to bulk update refunds.', 'error');
    }
  };

  // Handle export
  const handleExport = async () => {
    try {
      const url = await api.get('/api/admin/refunds/export', { params: filters });
      // Trigger download
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = 'refunds-report.csv';
      anchor.click();
      showToast('Success', 'Refunds exported.', 'success');
    } catch (err) {
      showToast('Error', 'Failed to export refunds.', 'error');
    }
  };

  if (loading) return <LoadingSpinner message='Loading admin refund dashboard...' />;
  if (!checkAdminAccess()) return <div className='p-6 text-center'><h3>Access Denied - Only administrators can view the settlement dashboard</h3></div>;

  return (
    <div className='max-w-7xl mx-auto p-6'>
      {/* Header with metrics */}
      <header className='mb-6'>
        <h1 className='text-3xl font-bold text-slate-900'>
          Admin Refund Dashboard
          <svg className='w-6 h-6 ml-2 text-indigo-600 fill-current' viewBox='0 0 24 24'>
            <path d='M3 13h2l7 7L7 19l-1-1 5.3-5.4L3 13zM3 15h2l7 5L7 21l-1-1 5.5-5.5L3 15z' />
          </svg>
        </h1>
        <p className='text-sm text-slate-500 mt-1'>
          Oversee all refund requests across the platform. Filter, search, and update status.
          <strong className='ml-2 text-indigo-600'>Note:</strong> This is platform-wide administrator view. Organizer payouts (accessible via /organizer/payouts) manage individual organizer earnings separately.
        </p>
      </header>

      {/* Metrics Summary */}
      <div className='grid grid-cols-2 md:grid-cols-4 gap-4 mb-6'>
        <div className='p-4 rounded-lg border border-slate-200'>
          <p className='text-sm text-slate-500'>Pending</p>
          <p className='text-2xl font-bold text-indigo-600'>{metrics.totalPending}</p>
        </div>
        <div className='p-4 rounded-lg border border-slate-200'>
          <p className='text-sm text-slate-500'>Pending Amount</p>
          <p className='text-2xl font-bold text-indigo-600'>${metrics.totalPendingAmount}</p>
        </div>
        <div className='p-4 rounded-lg border border-slate-200'>
          <p className='text-sm text-slate-500'>Approval Rate</p>
          <p className='text-2xl font-bold text-indigo-600'>{metrics.approvalRate}%</p>
        </div>
        <div className='p-4 rounded-lg border border-slate-200'>
          <p className='text-sm text-slate-500'>Avg Processing</p>
          <p className='text-2xl font-bold'>{metrics.averageProcessingTime} days</p>
        </div>
      </div>

      {/* Filters and Table */}
      <RefundFilterBar filters={filters} setFilters={setFilters} />
      <RefundTable
        refunds={refunds}
        columns={columns}
        onStatusUpdate={handleStatusUpdate}
        onBulkUpdate={handleBulkUpdate}
        onExport={handleExport}
      />
    </div>
  );
};

export default AdminRefundDashboardPage;

