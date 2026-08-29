import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { Skeleton, LoadingSpinner, ErrorBoundary } from '../../../components';
import { REFUND_STATUSES } from '../../../features/refunds/constants';
import { formatRefundStatus } from '../../../features/refunds/utils';
import { useRefunds } from '../../../features/refunds/hooks';
import { RefundStatusBadge } from '../components';
import { AppealModal } from '../components';

const UserRefundStatusPage = () => {
  const { ticketId, refundRequestId } = useParams();
  const { user, loading } = useAuthContext();
  const navigate = useNavigate();
  const location = useLocation();

  const [refund, setRefund] = useState(null);
  const [statusTimeline, setStatusTimeline] = useState([]);
  const [showAppeal, setShowAppeal] = useState(false);
  const [appealReason, setAppealReason] = useState('');

  // Load refund status data
  useEffect(() => {
    if (!ticketId && !refundRequestId) return;

    // Determine which ID to use
    const id = refundRequestId || ticketId;

    // Fetch refund status from backend
    // This will be replaced with actual API call
    const mockRefund = {
      id: refundRequestId || ticketId,
      ticketId,
      status: 'pending',
      amount: 0,
      reason: '',
      submittedAt: new Date(),
      processedAt: null,
      refundMethod: 'original_payment',
      policy: { windowDays: 7, fullRefundBeforeDays: 3 },
    };

    setRefund(mockRefund);

    // Build status timeline
    const timeline = [];
    if (mockRefund.status === 'pending') {
      timeline.push({ step: 'submitted', date: mockRefund.submittedAt, label: 'Submitted' });
    } else if (mockRefund.status === 'approved') {
      timeline.push({ step: 'submitted', date: mockRefund.submittedAt, label: 'Submitted' });
      timeline.push({ step: 'under_review', date: new Date(mockRefund.submittedAt.getTime() + 86400000), label: 'Under Review' });
      timeline.push({ step: 'approved', date: mockRefund.processedAt || new Date(), label: 'Approved' });
    } else if (mockRefund.status === 'rejected') {
      timeline.push({ step: 'submitted', date: mockRefund.submittedAt, label: 'Submitted' });
      timeline.push({ step: 'under_review', date: new Date(mockRefund.submittedAt.getTime() + 86400000), label: 'Under Review' });
      timeline.push({ step: 'rejected', date: mockRefund.processedAt || new Date(), label: 'Rejected', reason: 'Policy does not cover this request' });
    }
    setStatusTimeline(timeline);
  }, [ticketId, refundRequestId]);

  // Check if user owns this refund
  const ownsRefund = refund?.ticketId === ticketId || refund?.id === refundRequestId;

  if (!ownsRefund && refund) {
    return <div className='p-6 text-center'><h3>Access Denied - you do not own this refund request</h3></div>;
  }

  const handleAppeal = async () => {
    if (!appealReason.trim()) return;
    try {
      const response = await api.post(`/api/refunds/${refundRequestId}/appeal`, {
        reason: appealReason,
      });
      setShowAppeal(false);
      setAppealReason('');
      showToast('Appeal submitted', 'Your appeal has been submitted for review.', 'success');
    } catch (err) {
      showToast('Appeal failed', 'Failed to submit appeal. Please try again.', 'error');
    }
  };

  if (loading) return <LoadingSpinner message='Loading refund status...' />;
  if (!refundRequestId && !ticketId) return <div className='p-6'>Please navigate from a ticket page.</div>;

  return (
    <div className='max-w-2xl mx-auto p-6'>
      <div className='mb-6 border-b border-slate-200 pb-4'>
        <h1 className='text-2xl font-bold text-slate-900'>
          Refund Status
          <svg className='w-5 h-5 ml-2 text-indigo-600' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
            <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M9 5l7 7-7 7' />
          </svg>
        </h1>
      </div>

      {refund && (
        <div className='mb-6'>
          <RefundStatusBadge status={refund.status} />
          <p className='text-sm text-slate-500 mt-2'>
            Refund #{refund.id} - {formatRefundStatus(refund.status)}
          </p>
        </div>
      )

{refund && refund.status === 'rejected' && (
        <AppealModal
          isOpen={showAppeal}
          onClose={() => setShowAppeal(false)}
          onSubmit={handleAppeal}
          reason={appealReason}
          setReason={setAppealReason}
          refundId={refundRequestId || ticketId}
        />
      )}

      {refund && (
        <div className='grid grid-cols-2 gap-4 mb-6'>
          <div>
            <dl className='grid grid-cols-2 gap-2 text-sm'>
              <dt className='font-medium text-slate-600'>Amount</dt>
              <dd>${refund.amount}</dd>
              <dt className='font-medium text-slate-600'>Submitted</dt>
              <dd>{refund.submittedAt ? new Date(refund.submittedAt).toLocaleDateString() : 'N/A'}</dd>
              <dt className='font-medium text-slate-600'>Method</dt>
              <dd>{refund.refundMethod}</dd>
            </dl>
          </div>
          {refund.processedAt && (
            <div>
              <dt className='font-medium text-slate-600'>Processed</dt>
              <dd>{refund.processedAt ? new Date(refund.processedAt).toLocaleDateString() : 'N/A'}</dd>
            </div>
          )}
        </div>
      )}

      {refund && refund.status === 'pending' && (
        <div className='mt-4 p-3 bg-indigo-50 rounded-lg border border-indigo-200'>
          <p className='text-sm text-slate-700'>
            <svg className='w-4 h-4 mr-2 inline-block text-indigo-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M8 7h.5a2 2 0 012 2v.5a2 2 0 01-2 2h-2v-2H8z' />
              <span className='ml-2'>Typically processed within 5-7 business days. Status updates sent via email.</span>
            </svg>
          </p>
        </div>
      )}

      {refund && refund.status === 'approved' && (
        <div className='mt-4 p-3 bg-green-50 rounded-lg border border-green-200'>
          <p className='text-sm text-green-600'>
            <svg className='w-4 h-4 mr-2 inline-block text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M5 13l4 4L2 12l3.5-3.5L7.5 6l-2.5 5L12 1l3.5 3.5L15 6l-2.5 5L19.5 6l3.5 3.5L12 1l-3.5 3.5L8.5 6L5 13z' />
              <span className='ml-2'>Refund approved! Expect 3-5 business days for funds to appear in your account.</span>
          </p>
        </div>
      )}

      {refund && refund.status === 'completed' && (
        <div className='mt-4 p-3 bg-green-50 rounded-lg border border-green-200'>
          <p className='text-sm text-green-600'>
            <svg className='w-4 h-4 mr-2 inline-block text-green-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
              <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M5 13l4 4L2 12l3.5-3.5L7.5 6l-2.5 5L12 1l3.5 3.5L15 6l-2.5 5L19.5 6l3.5 3.5L12 1l-3.5 3.5L8.5 6L5 13z' />
              <span className='ml-2'>Refund completed! Funds have been transferred to your account.</span>
          </p>
          <p className='text-xs text-slate-500 mt-1'>Actual completion date: {refund.processedAt ? new Date(refund.processedAt).toLocaleDateString() : 'N/A'}</p>
        </div>
      )}

      {!refund && refundRequestId && (
        <div className='mt-6 p-4 bg-slate-50 rounded-lg border border-slate-200'>
          <p className='text-sm text-slate-500'>
            Refund request not found. It may still be processing or the request may have been cancelled.
          </p>
        </div>
      )}
    </div>
  ))
}
