import { useState, useEffect, useCallback } from 'react';
import { ticketingService } from '../services/ticketingService';

export const useTicketTiers = (eventId) => {
  const [tiers, setTiers] = useState([]);
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchTiers = useCallback(async () => {
    if (!eventId) {
      setError('Missing event ID');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const { event: ev, tiers: t } = await ticketingService.getTicketTiers(eventId);
      setEvent(ev);
      setTiers(t);
    } catch (err) {
      setError(err?.response?.data?.message || err.message || 'Failed to load ticket tiers');
    } finally {
      setLoading(false);
    }
  }, [eventId]);

  useEffect(() => {
    fetchTiers();
  }, [fetchTiers]);

  return { tiers, event, loading, error, refresh: fetchTiers, setTiers, setEvent };
};
