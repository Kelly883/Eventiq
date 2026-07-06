import { useState, useEffect } from 'react';
import { payoutService } from '../services';

export const usePayouts = (filters = {}) => {
  const [payouts, setPayouts] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchPayouts = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await payoutService.getOrganizerPayouts(filters);
      setPayouts(response.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const fetchSummary = async () => {
    try {
      const data = await payoutService.getOrganizerPayoutSummary();
      setSummary(data);
    } catch (err) {
      console.error('Failed to fetch summary:', err);
    }
  };

  useEffect(() => {
    fetchPayouts();
    fetchSummary();
  }, [JSON.stringify(filters)]);

  return {
    payouts,
    summary,
    loading,
    error,
    refetch: fetchPayouts,
  };
};
