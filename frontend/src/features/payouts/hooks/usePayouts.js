import { useState, useEffect, useCallback, useMemo } from 'react';
import { payoutService } from '../services';

export const usePayouts = (filters = {}) => {
  const [payouts, setPayouts] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // filters is commonly passed as a fresh object literal on every render;
  // this content-based key avoids re-fetching when only the reference
  // changes, while keeping the effect's dependency array a plain,
  // statically-analyzable value instead of an inline JSON.stringify call.
  const filtersKey = useMemo(() => JSON.stringify(filters), [filters]);

  const fetchPayouts = useCallback(async () => {
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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filtersKey]);

  const fetchSummary = useCallback(async () => {
    try {
      const data = await payoutService.getOrganizerPayoutSummary();
      setSummary(data);
    } catch (err) {
      console.error('Failed to fetch summary:', err);
    }
  }, []);

  useEffect(() => {
    fetchPayouts();
    fetchSummary();
  }, [fetchPayouts, fetchSummary]);

  return {
    payouts,
    summary,
    loading,
    error,
    refetch: fetchPayouts,
  };
};
