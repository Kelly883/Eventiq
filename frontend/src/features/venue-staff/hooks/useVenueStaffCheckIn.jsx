import { useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

export function useVenueStaffCheckIn(eventId) {
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [scannerActive, setScannerActive] = useState(false);
  const [scanResults, setScanResults] = useState([]);

  useEffect(() => {
    if (!eventId) return;
    let cancelled = false;

    async function fetchEvent() {
      setLoading(true);
      setError(null);
      try {
        const response = await api.get(`/events/${eventId}`);
        const eventData = response.data?.data || response.data;
        if (!cancelled) {
          setEvent(eventData);
          setScannerActive(eventData?.status === 'active');
        }
      } catch (err) {
        if (!cancelled) setError('Failed to load event details');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }

    fetchEvent();
    return () => { cancelled = true; };
  }, [eventId]);

  const startScanner = useCallback(() => setScannerActive(true), []);
  const stopScanner = useCallback(() => setScannerActive(false), []);

  const processScan = useCallback(async (ticketCode) => {
    try {
      const response = await api.post('/api/venue/check-in', {
        ticket_code: ticketCode,
        event_id: eventId,
        scanned_at: new Date().toISOString(),
        client_mutation_id: `${eventId}-${ticketCode}`,
      });
      setScanResults((prev) => [
        {
          id: Date.now(),
          ticketCode,
          timestamp: new Date(),
          status: 'success',
          message: response.data?.message || 'Check-in successful',
        },
        ...prev,
      ]);
      return response.data;
    } catch (err) {
      setScanResults((prev) => [
        {
          id: Date.now(),
          ticketCode,
          timestamp: new Date(),
          status: 'error',
          message: err.response?.data?.message || 'Check-in failed',
        },
        ...prev,
      ]);
      throw err;
    }
  }, [eventId]);

  return { event, loading, error, scannerActive, scanResults, startScanner, stopScanner, processScan };
}
