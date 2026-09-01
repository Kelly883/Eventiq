import React, { useEffect, useRef, useState } from 'react';
import { fraudService } from '../services/fraudService';

const FraudTransactionReviewModal = ({ alertId, onClose, onResolve }) => {
  const [alert, setAlert] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const dialogRef = useRef(null);

  // Fetch alert details on mount
  useEffect(() => {
    if (!alertId) return;
    async function loadAlert() {
      try {
        const result = await fraudService.getAlert(alertId);
        setAlert(result);
      } catch (err) {
        setError('Unable to load this fraud alert. Please try again.');
      } finally {
        setLoading(false);
      }
    }
    loadAlert();

    // Listen for alert updates via BroadcastChannel
    if (typeof window !== 'undefined') {
      const handleAlertUpdate = (e) => {
        if (e.data && e.data.type === 'alert-update' && e.data.id === alertId) {
          setAlert(e.data);
        }
      };
      window.addEventListener('alert-update', handleAlertUpdate);
      return () => window.removeEventListener('alert-update', handleAlertUpdate);
    }
  }, [alertId]);

  useEffect(() => {
    dialogRef.current?.focus();

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') onClose();
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [onClose]);

  return (
    <div
      className="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center"
      onClick={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
      <div
        ref={dialogRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby="fraud-review-title"
        tabIndex={-1}
        className="bg-white rounded-xl border border-slate-200 p-6 shadow-2xl max-w-2xl w-full transform overflow-hidden"
      >
        <div className="flex items-center justify-between">
          <h2 id="fraud-review-title" className="text-lg font-bold text-slate-800">
            Fraud Transaction Review
          </h2>
          <button
            onClick={onClose}
            aria-label="Close fraud transaction review"
            className="text-sm text-slate-500 hover:text-slate-700"
          >
            ✕
          </button>
        </div>

        <div className="p-6">
          {loading && <p className="text-sm text-slate-500">Loading alert details...</p>}
          {error && <p role="alert" className="text-sm text-red-600">{error}</p>}
          {alert && (
            <>
          <h3 className="text-lg font-bold text-slate-800 mb-4">
            Alert #{alert.id}
          </h3>

          <div className="mb-4">
            <p className="text-sm text-slate-500 mb-1">Event Type:</p>
            <p className="font-medium text-slate-800">{alert.event_type || 'Unknown'}</p>
          </div>

          <div className="mb-4">
            <p className="text-sm text-slate-500 mb-1">Risk Level:</p>
            <span
              className={`text-sm font-medium ${alert.riskLevel === 'critical' ? 'text-red-600' : alert.riskLevel === 'high' ? 'text-orange-600' : alert.riskLevel === 'medium' ? 'text-amber-600' : 'text-green-600'}`}
            >
              {alert.riskLevel}
            </span>
          </div>

          <div className="mb-4">
            <p className="text-sm text-slate-500 mb-1">Risk Score:</p>
            <p className="font-medium text-slate-800">{alert.risk_score}</p>
          </div>

          <div className="mb-4">
            <p className="text-sm text-slate-500 mb-1">Status:</p>
            <p className="font-medium text-slate-500">
              {alert.status === 'pending' ? 'Pending Review'
                : alert.status === 'resolved' ? 'Resolved'
                : alert.status === 'dismissed' ? 'Dismissed'
                : 'Under Review'}
            </p>
          </div>

          <div className="mb-4">
            <p className="text-sm text-slate-500 mb-1">Description:</p>
            <p className="font-medium text-slate-500">{alert.description || 'No description'}</p>
          </div>

          <div className="mb-6">
            <p className="text-sm text-slate-500 mb-1">Decisions:</p>
            <div className="flex gap-4">
              <button
                onClick={() => onResolve(alertId, 'approve')}
                className="flex-1 px-4 py-2 rounded-lg bg-green-500 text-white text-sm font-medium hover:bg-green-600 transition-colors"
              >
                Approve
              </button>
              <button
                onClick={() => onResolve(alertId, 'reject')}
                className="flex-1 px-4 py-2 rounded-lg bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition-colors"
              >
                Reject
              </button>
            </div>
          </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default FraudTransactionReviewModal;