import { useState, useEffect } from 'react';
import { analyticsService } from '../services';

export const useSalesVelocity = (eventId, interval = 'daily') => {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchSalesVelocity = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await analyticsService.getSalesVelocity(eventId, interval);
      setData(response || []);
    } catch (err) {
      setError(err.message || 'Failed to fetch sales velocity');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (eventId) {
      fetchSalesVelocity();
    }
  }, [eventId, interval]);

  return {
    data,
    loading,
    error,
    refetch: fetchSalesVelocity,
  };
};

