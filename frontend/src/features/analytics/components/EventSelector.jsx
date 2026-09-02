import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { api } from '../../../lib/api';

const RECENT_EVENTS_KEY = 'eventiq_recent_events';
const MAX_RECENT_EVENTS = 3;

/*
 * Buckets the various backend event statuses into the three states the
 * selector renders. The API's EventResource maps 'published' -> 'live' and
 * 'archived' -> 'past', so both spellings must be understood here.
 */
const normalizeStatus = (status) => {
  const s = String(status || '').toLowerCase();
  if (['active', 'live', 'published', 'ongoing', 'running'].includes(s)) return 'active';
  if (['upcoming', 'scheduled', 'draft'].includes(s)) return 'upcoming';
  if (['ended', 'completed', 'finished', 'archived', 'past', 'cancelled', 'canceled'].includes(s)) return 'ended';
  return 'unknown';
};

const normalizeEvent = (e) => ({
  id: e.id,
  name: e.name || e.title || `Event #${e.id}`,
  date: e.date || (e.start_datetime ? String(e.start_datetime).slice(0, 10) : ''),
  status: normalizeStatus(e.status),
});

/* Active events first, then upcoming, then unknown, ended last; date asc inside. */
const sortEvents = (list) => {
  const weight = { active: 0, upcoming: 1, unknown: 2, ended: 3 };
  return [...list].sort((a, b) => {
    const byStatus = (weight[a.status] ?? 9) - (weight[b.status] ?? 9);
    if (byStatus !== 0) return byStatus;
    return String(a.date || '').localeCompare(String(b.date || ''));
  });
};

const getRecentEvents = () => {
  try {
    const stored = localStorage.getItem(RECENT_EVENTS_KEY);
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
};

const addRecentEvent = (eventId) => {
  const recent = getRecentEvents().map(String);
  const filtered = recent.filter((id) => id !== String(eventId));
  const updated = [String(eventId), ...filtered].slice(0, MAX_RECENT_EVENTS);
  localStorage.setItem(RECENT_EVENTS_KEY, JSON.stringify(updated));
};

/*
 * Event selector fed by the real /events API (previously hardcoded mock
 * events — staff could never see their actual events). Fully controlled via
 * the `selectedEventId` prop; selection is reported through `onSelect` when
 * provided, otherwise the current page's ?eventId= query param is updated.
 */
const EventSelector = ({ compact = false, showLabel = true, selectedEventId: controlledEventId, onSelect }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [isOpen, setIsOpen] = useState(false);
  const [recentEventIds, setRecentEventIds] = useState([]);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    let cancelled = false;
    const fetchEvents = async () => {
      setLoading(true);
      setError('');
      try {
        const response = await api.get('/events');
        const list = (response.data?.data || response.data || []).map(normalizeEvent);
        if (!cancelled) {
          setEvents(sortEvents(list));
          setRecentEventIds(getRecentEvents().map(String));
        }
      } catch (err) {
        if (!cancelled) {
          setError(err?.response?.data?.message || 'Failed to load events.');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    fetchEvents();
    return () => { cancelled = true; };
  }, [reloadKey]);

  const selectedEventId = controlledEventId != null && controlledEventId !== '' ? String(controlledEventId) : null;
  const selectedEvent = events.find((e) => String(e.id) === selectedEventId) || null;
  const recentEvents = recentEventIds
    .map((id) => events.find((e) => String(e.id) === id))
    .filter(Boolean)
    .filter((e) => String(e.id) !== selectedEventId);

  const handleSelect = (eventId) => {
    setIsOpen(false);
    if (eventId != null && eventId !== '') {
      addRecentEvent(eventId);
      setRecentEventIds(getRecentEvents().map(String));
    }

    if (onSelect) {
      onSelect(eventId);
      return;
    }

    // Default behaviour: keep the caller on the same /check-in page and
    // update its ?eventId= query parameter (the check-in desk).
    const base = location.pathname.split('?')[0];
    const params = new URLSearchParams(location.search);
    if (eventId != null && eventId !== '') params.set('eventId', String(eventId));
    else params.delete('eventId');
    navigate(`${base}?${params.toString()}`);
  };

  const retry = () => setReloadKey((k) => k + 1);

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-emerald-500';
      case 'upcoming': return 'bg-amber-500';
      case 'ended': return 'bg-slate-400';
      default: return 'bg-slate-300';
    }
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'active': return 'Live';
      case 'upcoming': return 'Upcoming';
      case 'ended': return 'Ended';
      default: return '—';
    }
  };

  const buttonLabel = loading
    ? 'Loading events…'
    : error
      ? 'Events unavailable'
      : selectedEvent?.name || 'Select Event';

  if (compact) {
    return (
      <div className="relative">
        <button
          type="button"
          aria-label="Select event"
          aria-haspopup="listbox"
          aria-expanded={isOpen}
          onClick={() => setIsOpen((open) => !open)}
          className="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm hover:bg-slate-50 transition-colors"
        >
          <span className={`w-2 h-2 rounded-full ${selectedEvent ? getStatusColor(selectedEvent.status) : 'bg-slate-300'}`} />
          <span className="text-sm font-medium text-slate-700 truncate max-w-[150px]">
            {buttonLabel}
          </span>
          <svg aria-hidden="true" className={`w-4 h-4 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        {isOpen && (
          <>
            <div className="fixed inset-0 z-10" aria-hidden="true" onClick={() => setIsOpen(false)} />
            <div role="listbox" aria-label="Events" className="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
              {loading && (
                <div className="p-4 text-sm text-slate-500" role="status">Loading events…</div>
              )}
              {!loading && error && (
                <div className="p-4 text-sm">
                  <p className="text-red-600">{error}</p>
                  <button
                    type="button"
                    onClick={retry}
                    className="mt-2 text-indigo-600 hover:text-indigo-800 text-xs font-semibold underline"
                  >
                    Retry
                  </button>
                </div>
              )}
              {!loading && !error && (
                <>
                  {recentEvents.length > 0 && (
                    <>
                      <div className="p-2 border-b border-slate-100">
                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider px-2">Recent</span>
                      </div>
                      <div>
                        {recentEvents.map((event) => (
                          <button
                            key={`recent-${event.id}`}
                            type="button"
                            onClick={() => handleSelect(event.id)}
                            className={`w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors text-left ${
                              String(event.id) === selectedEventId ? 'bg-indigo-50' : ''
                            }`}
                          >
                            <span className="text-sm">🕐</span>
                            <div className="flex-1 min-w-0">
                              <div className="text-sm font-medium text-slate-800 truncate">{event.name}</div>
                            </div>
                          </button>
                        ))}
                      </div>
                      <div className="border-t border-slate-100" />
                    </>
                  )}
                  <div className="p-2 border-b border-slate-100">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider px-2">All Events</span>
                  </div>
                  <div className="max-h-64 overflow-y-auto">
                    {events.length === 0 && (
                      <div className="p-4 text-sm text-slate-500">No events available.</div>
                    )}
                    {events.map((event) => (
                      <button
                        key={event.id}
                        type="button"
                        role="option"
                        aria-selected={String(event.id) === selectedEventId}
                        onClick={() => handleSelect(event.id)}
                        className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors text-left ${
                          String(event.id) === selectedEventId ? 'bg-indigo-50' : ''
                        }`}
                      >
                        <span className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${getStatusColor(event.status)}`} />
                        <div className="flex-1 min-w-0">
                          <div className="text-sm font-medium text-slate-800 truncate">{event.name}</div>
                          <div className="text-xs text-slate-500">{event.date}</div>
                        </div>
                        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                          event.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                          event.status === 'upcoming' ? 'bg-amber-100 text-amber-700' :
                          'bg-slate-100 text-slate-600'
                        }`}>
                          {getStatusLabel(event.status)}
                        </span>
                      </button>
                    ))}
                  </div>
                </>
              )}
            </div>
          </>
        )}
      </div>
    );
  }

  return (
    <div className="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
      {showLabel && (
        <label className="text-xs font-semibold text-slate-500 uppercase tracking-wider block mb-3">
          Current Event
        </label>
      )}
      <div className="flex items-center gap-3">
        <div className="flex-1">
          <select
            aria-label="Select event"
            value={selectedEventId || ''}
            onChange={(e) => handleSelect(e.target.value)}
            disabled={loading || Boolean(error)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-slate-50 disabled:text-slate-400"
          >
            {loading && <option value="">Loading events…</option>}
            {!loading && error && <option value="">Failed to load events</option>}
            {!loading && !error && (
              <>
                <option value="">Select an event...</option>
                {events.map((event) => (
                  <option key={event.id} value={String(event.id)}>
                    {event.name} ({event.date})
                  </option>
                ))}
              </>
            )}
          </select>
        </div>
        {error && (
          <button
            type="button"
            onClick={retry}
            className="text-xs font-semibold text-indigo-600 hover:text-indigo-800 underline"
          >
            Retry
          </button>
        )}
        {selectedEvent && !error && (
          <span className={`px-2.5 py-1 rounded-full text-xs font-bold ${
            selectedEvent.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
            selectedEvent.status === 'upcoming' ? 'bg-amber-100 text-amber-700' :
            'bg-slate-100 text-slate-600'
          }`}>
            {getStatusLabel(selectedEvent.status)}
          </span>
        )}
      </div>
    </div>
  );
};

export default EventSelector;
