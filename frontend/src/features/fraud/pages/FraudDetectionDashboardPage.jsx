import React, { useEffect, useState, useRef, useCallback } from 'react';
import { useAuthContext } from '../../auth/context/AuthContext';
import { useNavigate } from 'react-router-dom';
import { fraudService } from '../services/fraudService';
import FraudTransactionReviewModal from '../pages/FraudTransactionReviewModal';

const FraudDetectionDashboardPage = () => {
  const { user, loading } = useAuthContext();
  const navigate = useNavigate();
  const [alerts, setAlerts] = useState([]);
  const [stats, setStats] = useState({
    totalAlertsToday: 0,
    pendingReview: 0,
    criticalAlerts: 0,
    resolvedToday: 0,
    avgRiskScore: 0,
    fraudPreventionRate: 0,
    flaggedRevenue: 0,
  });
  const [resolvingAlert, setResolvingAlert] = useState(null);
  const alertRefs = useRef({});
  const setAlertRef = useCallback((id, el) => {
    alertRefs.current[id] = el;
  }, []);

  const handleCloseModal = useCallback(() => {
    const closedAlertId = resolvingAlert;
    setResolvingAlert(null);
    requestAnimationFrame(() => {
      if (closedAlertId != null && alertRefs.current[closedAlertId]) {
        alertRefs.current[closedAlertId]?.focus();
      }
    });
  }, [resolvingAlert]);

  // Fetch alerts and stats on mount
  useEffect(() => {
    if (!user) return;
    async function loadData() {
      try {
        const [alertsResult, statsResult] = await Promise.all([
          fraudService.listAlerts(),
          fraudService.getDashboardStats(),
        ]);
        setAlerts(alertsResult || []);
        setStats(statsResult || {
          totalAlertsToday: 0,
          pendingReview: 0,
          criticalAlerts: 0,
          resolvedToday: 0,
          avgRiskScore: 0,
          fraudPreventionRate: 0,
          flaggedRevenue: 0,
        });
      } catch (err) {
        console.error('Failed to load fraud dashboard data', err);
      }
    }
    loadData();

    // Listen for alert changes via BroadcastChannel or re-fetch periodically
    const load = () => loadData();
    const handleBroadcast = (e) => {
      if (e.data && e.data.type === 'alert-update') {
        loadData();
      }
    };
    if (typeof window !== 'undefined') {
      window.addEventListener('alert-update', handleBroadcast);
    }
    return () => window.removeEventListener('alert-update', handleBroadcast);
  }, [user]);

  // Handle alert resolution
  const handleResolve = async (alertId, decision) => {
    setResolvingAlert(alertId);
    try {
      await fraudService.resolveAlert(alertId, { decision });
      setAlerts((prev) => prev.map((a) => a.id === alertId ? { ...a, status: decision === 'approve' ? 'resolved' : decision } : a));
    } catch (err) {
      console.error('Failed to resolve alert', err);
    } finally {
      setResolvingAlert(null);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
              Fraud Detection Dashboard
            </h1>
            <p className="mt-2 text-sm text-slate-500">
              Monitor and review flagged transactions to ensure platform security.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100 flex items-center gap-1.5">
              <span className="h-2 w-2 bg-indigo-500 rounded-full" />
              Admin view
            </span>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 mb-6">
          {/* Left: Alerts List */}
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-800 mb-4">Flagged Alerts</h2>
            {alerts.length === 0 ? (
              <p className="text-sm text-slate-500 mb-4">No alerts at this time.</p>
            ) : (
              <div className="space-y-2">
                {alerts.map((alert) => (
                  <div
                    key={alert.id}
                    ref={(el) => setAlertRef(alert.id, el)}
                    role="button"
                    tabIndex={0}
                    className="p-4 border-l-4 border-red-500 rounded bg-red-50 hover:bg-red-100 transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-500"
                    onClick={() => setResolvingAlert(alert.id)}
                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setResolvingAlert(alert.id); } }}
                  >
                    <div className="flex items-start">
                      <div className="w-8 rounded-xl bg-red-100 flex items-center justify-center text-sm font-medium text-red-600">
                        {alert.riskLevel}
                      </div>
                      <div className="ml-3 flex-1">
                        <p className="font-medium text-slate-800">{alert.event_type || 'Unknown event'}</p>
                        <p className="text-xs text-slate-500">Risk: {alert.riskLevel} (${alert.risk_score})</p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            <div className="mt-4 p-4 bg-white rounded-xl border border-slate-200">
              <h3 className="text-lg font-bold text-slate-800 mb-2">Quick Stats</h3>
              <div className="grid grid-cols-2">
                <div>
                  <p className="text-xs text-slate-500 mb-1">Total Alerts Today</p>
                  <p className="text-2xl font-bold text-slate-900">{stats.totalAlertsToday}</p>
                </div>
                <div>
                  <p className="text-xs text-slate-500 mb-1">Pending Review</p>
                  <p className="text-2xl font-bold text-indigo-600">{stats.pendingReview}</p>
                </div>
                <div>
                  <p className="text-xs text-slate-500 mb-1">Critical Alerts</p>
                  <p className="text-2xl font-bold text-red-600">{stats.criticalAlerts}</p>
                </div>
                <div>
                  <p className="text-xs text-slate-500 mb-1">Fraud Prevention Rate</p>
                  <p className="text-2xl font-bold text-green-600">{stats.fraudPreventionRate}%</p>
                </div>
              </div>
            </div>
          </div>

          {/* Right: Dashboard stats */}
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-800 mb-4">Dashboard Overview</h2>
            <div>
              <p className="text-xs text-slate-500 mb-1">Resolved Today</p>
              <p className="text-2xl font-bold text-slate-900">{stats.resolvedToday}</p>
              <p className="text-xs text-slate-500 mb-1">Flagged Revenue</p>
              <p className="text-2xl font-bold text-green-600">{stats.flaggedRevenue}</p>
            </div>
          </div>
        </div>

        {/* Fraud Transaction Review Modal */}
        {resolvingAlert !== null ? (
          <FraudTransactionReviewModal
            alertId={resolvingAlert}
            onClose={handleCloseModal}
            onResolve={handleResolve}
          />
        ) : null}
      </div>
    </div>
  );
};

export default FraudDetectionDashboardPage;