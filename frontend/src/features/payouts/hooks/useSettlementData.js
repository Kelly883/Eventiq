import { useState, useEffect, useCallback, useMemo } from 'react';
import { payoutService } from '../services';

export const useSettlementData = (filters = {}) => {
  const [settlements, setSettlements] = useState([]);
  const [summary, setSummary] = useState(null);
  const [policies, setPolicies] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Same content-based key pattern as usePayouts.js - avoids re-fetching
  // when filters gets a fresh object reference with the same content.
  const filtersKey = useMemo(() => JSON.stringify(filters), [filters]);

  const fetchSettlements = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await payoutService.getAdminSettlements(filters);
      setSettlements(response.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filtersKey]);

  const fetchSummary = useCallback(async () => {
    try {
      const data = await payoutService.getAdminSettlementSummary();
      setSummary(data);
    } catch (err) {
      console.error('Failed to fetch settlement summary:', err);
    }
  }, []);

  const fetchPolicies = useCallback(async () => {
    try {
      const data = await payoutService.getSettlementPolicies();
      setPolicies(data);
    } catch (err) {
      console.error('Failed to fetch policies:', err);
    }
  }, []);

  useEffect(() => {
    fetchSettlements();
    fetchSummary();
    fetchPolicies();
  }, [fetchSettlements, fetchSummary, fetchPolicies]);

  const processPayout = async (payoutId) => {
    try {
      await payoutService.processPayout(payoutId);
      await fetchSettlements();
      await fetchSummary();
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    settlements,
    summary,
    policies,
    loading,
    error,
    refetch: fetchSettlements,
    processPayout,
  };
};
