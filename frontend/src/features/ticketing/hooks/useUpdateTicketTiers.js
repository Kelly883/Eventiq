import { useState, useCallback } from 'react';
import { ticketingService } from '../services/ticketingService';

export const useUpdateTicketTiers = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const update = useCallback(async (eventId, tiers) => {
    setLoading(true);
    setError(null);
    try {
      const result = await ticketingService.updateTicketTiers(eventId, tiers);
      return result;
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to save ticket tiers';
      setError(msg);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  return { update, loading, error };
};
