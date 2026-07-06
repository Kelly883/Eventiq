import { useEffect, useState } from 'react';

export const useEventModeration = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [events, setEvents] = useState([]);
  const [selectedEvent, setSelectedEvent] = useState(null);
  const [filters, setFilters] = useState({});
  const [moderationNotes, setModerationNotes] = useState('');

  const fetchEvents = async () => {
    setLoading(true);
    setError(null);
    try {
      // Placeholder
      setEvents([]);
    } catch (e) {
      setError(e?.message ?? 'Failed to load events');
    } finally {
      setLoading(false);
    }
  };

  const updateModeration = async ({ notes }) => {
    setModerationNotes(notes);
    // Placeholder
  };

  useEffect(() => {
    fetchEvents();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(filters)]);

  return {
    loading,
    error,
    events,
    selectedEvent,
    filters,
    setFilters,
    moderationNotes,
    setSelectedEvent,
    updateModeration,
    refetch: fetchEvents,
  };
};

