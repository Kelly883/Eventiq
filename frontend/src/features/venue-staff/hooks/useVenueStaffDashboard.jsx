import { useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

export function useVenueStaffDashboard() {
  const [stats, setStats] = useState({
    totalEvents: 0,
    activeEvents: 0,
    pendingCheckIns: 0,
    completedCheckIns: 0,
  });
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchDashboardData = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const eventsRes = await api.get('/events', { params: { venue_access: 'true' } });
      const eventsData = eventsRes.data?.events || eventsRes.data?.data || [];
      const list = Array.isArray(eventsData) ? eventsData : [];
      setEvents(list);
      setStats({
        totalEvents: list.length,
        activeEvents: list.filter((e) => e.status === 'active').length,
        pendingCheckIns: 0,
        completedCheckIns: 0,
      });
    } catch (err) {
      setError('Failed to load venue dashboard data');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchDashboardData();
  }, [fetchDashboardData]);

  return { stats, events, loading, error, refetch: fetchDashboardData };
}
