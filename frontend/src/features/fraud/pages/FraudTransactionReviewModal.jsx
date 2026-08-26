import React, { useEffect, useState } from 'react';
import { useAuthContext } from '../../auth/context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { fraudService } from '../services/fraudService';

const FraudTransactionReviewModal = ({ alertId, onClose }) => {
  const { user } = useAuthContext();
  const navigate = useNavigate();
  const [alert, setAlert] = useState(null);
  const [loading, setLoading] = useState(true);

  // Fetch alert details on mount
  useEffect(() => {
    if (!alertId) return;
    async function loadAlert() {
      try {
        const result = await fraudService.getAlert(alertId);
        setAlert(result);
        setLoading(false);
      } catch (err) {
        console.error('Failed to load alert', err);
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

  if (!alert) {
    return null;
  }

  const handleResolve = async (decision) => {
    try {
      await fraudService.resolveAlert(alertId, decision);
      onClose();
    } catch (err) {
      console.error('Failed to resolve alert', err);
    }
  };

  return (
    <div
      className="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center"
      onClick={e => e.target !== e.currentTarget && onClose()}
    >
      <div
        className="bg-white rounded-xl border border-slate-200 p-6 shadow-2xl max-w-2xl w-full transform overflow-hidden"
        onClick={e => e.stopPropagation()}
      >
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-bold text-slate-800">
            Fraud Transaction Review
          </h2>
          <button
            onClick={onClose}
            className="text-sm text-slate-500 hover:text-slate-700"
          >
            ✕
          </button>
        </div>

        <div className="p-6">
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
                onClick={() => handleResolve('approve')}
                className="flex-1 px-4 py-2 rounded-lg bg-green-500 text-white text-sm font-medium hover:bg-green-600 transition-colors"
              >
                Approve
              </button>
              <button
                onClick={() => handleResolve('reject')}
                className="flex-1 px-4 py-2 rounded-lg bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition-colors"
              >
                Reject
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FraudTransactionReviewModal;