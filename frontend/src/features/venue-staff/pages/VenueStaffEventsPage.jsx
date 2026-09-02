import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';
import EmptyState from '../../../components/EmptyState';

const VenueStaffEventsPage = () => {
  const navigate = useNavigate();
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    async function fetchEvents() {
      setLoading(true);
      setError(null);
      try {
        const res = await api.get('/events', { params: { venue_access: 'true' } });
        if (!cancelled) {
          const eventsData = res.data?.events || res.data?.data || res.data || [];
          setEvents(Array.isArray(eventsData) ? eventsData : []);
        }
      } catch (err) {
        if (!cancelled) setError('Failed to load events for check-in');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchEvents();
    return () => { cancelled = true; };
  }, []);

  const handleStartCheckIn = (eventId) => {
    navigate(`/check-in?eventId=${eventId}`);
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'upcoming': return 'bg-blue-100 text-blue-800';
      case 'ended': return 'bg-gray-100 text-gray-800';
      default: return 'bg-slate-100 text-slate-800';
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-7xl">
          <Skeleton lines={5} />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Event Check-In Dashboard
          </h1>
          <p className="mt-2 text-slate-600">
            Select an event to start scanning tickets and attendee check-ins.
          </p>
        </div>

        {error && (
          <div className="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
            <div className="text-sm text-red-800">{error}</div>
          </div>
        )}

        {events.length === 0 ? (
          <EmptyState
            title="No Events Available"
            description="There are currently no events available for check-in. Contact your organizer for more information."
            actionLabel="View My Tickets"
            onAction={() => navigate('/my-tickets')}
          />
        ) : (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {events.map((event) => (
              <div
                key={event.id}
                className="group cursor-pointer rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md hover:border-slate-300"
                onClick={() => handleStartCheckIn(event.id)}
              >
                <div className="mb-4 flex items-start justify-between">
                  <h3 className="text-lg font-semibold text-slate-900 group-hover:text-blue-600">
                    {event.name || event.title || `Event #${event.id}`}
                  </h3>
                  <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${getStatusColor(event.status)}`}>
                    {event.status || 'unknown'}
                  </span>
                </div>
                <div className="mt-6 flex items-center justify-between">
                  <button
                    onClick={(e) => { e.stopPropagation(); handleStartCheckIn(event.id); }}
                    className="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                  >
                    Start Check-In
                  </button>
                  <div className="text-xs text-slate-400">Tap to view details</div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default VenueStaffEventsPage;
