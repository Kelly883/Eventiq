import { useState, useEffect } from 'react';
import { payoutService } from '../services';

export const useSettlementData = (filters = {}) => {
  const [settlements, setSettlements] = useState([]);
  const [summary, setSummary] = useState(null);
  const [policies, setPolicies] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchSettlements = async () => {
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
  };

  const fetchSummary = async () => {
    try {
      const data = await payoutService.getAdminSettlementSummary();
      setSummary(data);
    } catch (err) {
      console.error('Failed to fetch settlement summary:', err);
    }
  };

  const fetchPolicies = async () => {
    try {
      const data = await payoutService.getSettlementPolicies();
      setPolicies(data);
    } catch (err) {
      console.error('Failed to fetch policies:', err);
    }
  };

  useEffect(() => {
    fetchSettlements();
    fetchSummary();
    fetchPolicies();
  }, [JSON.stringify(filters)]);

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
