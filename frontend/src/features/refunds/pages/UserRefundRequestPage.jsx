import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { Skeleton, LoadingSpinner, ErrorBoundary, Toast } from '../../../components';
import { REFUND_STATUSES } from '../../../features/refunds/constants';
import { calculateRefundAmount, formatRefundStatus } from '../../../features/refunds/utils';
import { useRefunds } from '../../../features/refunds/hooks';
import { RefundStatusBadge } from '../components';
import { AppealModal } from '../components';

const UserRefundRequestPage = () => {
  const { ticketId } = useParams();
  const { user, loading, checkAdminAccess } = useAuthContext();
  const navigate = useNavigate();
  const location = useLocation();

  const [form, setForm] = useState({
    reason: '',
    explanation: '',
    refundMethod: 'original_payment',
  });
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showSuccess, setShowSuccess] = useState(false);
  const [showError, setShowError] = useState(false);

  const validateForm = () => {
    const newErrors = {};
    if (!form.reason.trim()) newErrors.reason = 'Refund reason is required';
    if (form.refundMethod <= 0) newErrors.refundMethod = 'Refund method is required';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    setIsSubmitting(true);

    try {
      const response = await api.post('/api/refunds/request', {
        ticketId,
        ...form,
      });

      setShowSuccess(true);
      setIsSubmitting(false);

      setTimeout(() => {
        navigate(`/my-tickets/${ticketId}/refund-status`);
        setShowSuccess(false);
      }, 3000);
    } catch (err) {
      setIsSubmitting(false);
      if (err.response?.status === 403) {
        setShowError(true);
        setError('You can only request refunds for tickets you own. Please select your ticket from My Tickets.');
      } else if (err.response?.status === 404) {
        setShowError(true);
        setError('Ticket not found. Please verify the ticket ID.');
      } else {
        setShowError(true);
        setError('Failed to submit refund request. Please try again.');
      }
    }
  };

  const handleErrorClose = () => setShowError(false);

  if (loading) return <LoadingSpinner message='Loading ticket information...' />;
  if (!ticketId) return <div className='p-6'>Please select a ticket from My Tickets.</div>;

  return (
    <div className='max-w-2xl mx-auto p-6'>
      <div className='mb-6 border-b border-slate-200 pb-4'>
        <h1 className='text-2xl font-bold text-slate-900'>
          Request a Refund
          <svg className='w-5 h-5 ml-2 text-indigo-600' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
            <path strokeLinecap='round' strokeLinejoin='round' strokeWidth={2} d='M9 5l7 7-7 7' />
          </svg>
        </h1>
        <p className='text-sm text-slate-500'>
          Request money back for ticket #{ticketId}. Refunds typically take 5-7 business days.
        </p>
      </div>

      {showSuccess && (
        <div className='p-4 bg-green-50 rounded-lg border border-green-200 mb-4'>
          <p className='text-sm text-green-600'>
            Refund request submitted successfully! <a href={`/my-tickets/${ticketId}/refund-status`} className='font-medium text-green-600 hover:underline'>
              View refund status
            </a>
          </p>
        </div>
      )}

      <form onSubmit={handleSubmit} className='space-y-4'>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-2'>
            Refund Reason <span className='text-red-500'>*</span>
          </label>
          <select
            value={form.reason}
            onChange={(e) => setForm({ ...form, reason: e.target.value })} 
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
            required
          >
            <option value='' disabled>Select a reason</option>
            <option value='event_cancelled'>Event was cancelled</option>
            <option value='cannot_attend'>Cannot attend</option>
            <option value='duplicate_purchase'>Duplicate purchase</option>
            <option value='other'>Other</option>
          </select>
          {errors.reason && <p className='mt-1 text-xs text-red-600'>{errors.reason}</p>}
        </div>

        <div>
          <label className='block text-sm font-medium text-slate-700 mb-2'>
            Explanation (optional)
          </label>
          <textarea
            value={form.explanation}
            onChange={(e) => setForm({ ...form, explanation: e.target.value })} 
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
            rows={3}
            placeholder='Enter any additional details about your refund request'
          />
        </div>

        <div>
          <label className='block text-sm font-medium text-slate-700 mb-2'>
            Refund Method
          </label>
          <select
            value={form.refundMethod}
            onChange={(e) => setForm({ ...form, refundMethod: e.target.value })} 
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
            required
          >
            <option value='' disabled>Select method</option>
            <option value='original_payment'>Original payment method</option>
            <option value='store_credit'>Store credit</option>
            <option value='bank_transfer'>Bank transfer</option>
          </select>
        </div>

        <button
          type='submit'
          disabled={isSubmitting}
          className={isSubmitting ? 'px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-500' : 'px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700'}
        >
          {isSubmitting ? 'Submitting...' : 'Request Refund'}
        </button>
      </form>

      {showError && (
        <div className='p-4 bg-red-50 rounded-lg border border-red-200 mb-4'>
          <p className='text-sm text-red-600'>{error.message}</p>
          <button onClick={handleErrorClose} className='ml-2 text-sm text-indigo-600 hover:underline'>Close</button>
        </div>
      )}

      <div className='mt-6 p-4 bg-slate-50 rounded-lg border border-slate-200'>
        <h3 className='font-medium text-slate-800 mb-2'>Refund Policy</h3>
        <p className='text-sm text-slate-500'>
          Refunds are subject to the event organizer's refund policy. 
          <a href='/refund-policy' className='font-medium text-indigo-600 underline'>
            View full refund policy
          </a>
        </p>
        <p className='text-xs text-slate-400 mt-1'>
          Typical processing time: 5-7 business days
        </p>
      </div>
    </div>
}

