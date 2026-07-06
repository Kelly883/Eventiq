import { useEffect, useState } from 'react';

export const useAdminDashboard = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [metrics, setMetrics] = useState(null);
  const [activity, setActivity] = useState([]);
  const [alerts, setAlerts] = useState([]);
  const [quickStats, setQuickStats] = useState(null);

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
    fetchData();
  }, []);

  return { loading, error, metrics, activity, alerts, quickStats, refetch: fetchData };
};

