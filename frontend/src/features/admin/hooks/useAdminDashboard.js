import { useEffect, useState } from 'react';

// Debounce function to limit the rate of fetchData calls
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

export const useAdminDashboard = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [metrics, setMetrics] = useState(null);
  const [activity, setActivity] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [quickStats, setQuickStats] = useState(null);

  // placeholderData used during initial load or when refetching
  const placeholderData = {
    metrics: { totalRevenue: 0, activeEvents: 0, pendingApprovals: 0, flaggedTransactions: 0, failedPayouts: 0, revenueTrend: 'flat', eventsTrend: 'flat', transactionsTrend: 'flat' },
    activity: [],
    alerts: [],
    quickStats: {},
  };

  const fetchData = async () => {
    setLoading(true);
    setError(null);
    try {
      // Placeholder: wire to API later.
      setMetrics({});
      setQuickStats({});
      setActivity([]);
      setAlerts([]);
    } catch (e) {
      setError(e?.message ?? 'Failed to load dashboard');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // Initial fetch
    fetchData();

    // 30s debounced polling - uses setTimeout instead of setInterval
    // Each fetch resets the timer, effectively debouncing rapid calls
    let timeoutId;
    const fetchWithDelay = async () => {
      timeoutId = setTimeout(async () => {
        try {
          await fetchData();
        } finally {
          // Reschedule for next poll cycle
          fetchWithDelay();
        }
      }, 30000);
    };

    fetchWithDelay();

    return () => clearTimeout(timeoutId);
  }, []);

  return { loading, error, metrics, activity, alerts, quickStats, placeholderData, refetch: fetchData };
};

