import React, { useEffect, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { api } from '../../../lib/api';

const RECENT_EVENTS_KEY = 'eventiq_recent_events';
const MAX_RECENT_EVENTS = 3;
const MAX_SELECTED_EVENTS = 3;

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

const EventSelector = ({ compact = false, showLabel = true, selectedEventIds: controlledSelectedEventIds, onSelect }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [isOpen, setIsOpen] = useState(false);
  const [recentEventIds, setRecentEventIds] = useState([]);
  const [reloadKey, setReloadKey] = useState(0);
  const [selectedEventIds, setSelectedEventIds] = useState(() => {
    if (controlledSelectedEventIds != null && controlledSelectedEventIds !== '') {
      return controlledSelectedEventIds.split(',').filter(Boolean).map(Number) || [];
    }
    return [];
  });

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

  const selectedEventIdsState = controlledSelectedEventIds != null && controlledSelectedEventIds !== '' ? controlledSelectedEventIds.split(',').filter(Boolean).map(Number) : selectedEventIds;
  const selectedEvents = events.filter((e) => selectedEventIdsState.includes(e.id));
  const remainingEvents = events.filter((e) => !selectedEventIdsState.includes(e.id));

  const handleSelect = (eventId) => {
    if (selectedEventIdsState.length >= MAX_SELECTED_EVENTS && !selectedEventIdsState.includes(eventId)) {
      return; // Max 3 events selected, ignore new selection
    }

    let newSelectedIds;
    if (selectedEventIdsState.includes(eventId)) {
      newSelectedIds = selectedEventIdsState.filter((id) => id !== eventId);
    } else {
      newSelectedIds = [...selectedEventIdsState, eventId];
    }

    setSelectedEventIds(newSelectedIds);
    addRecentEvent(eventId);

    if (onSelect) {
      onSelect(newSelectedIds.join(','));
      return;
    }

    const base = location.pathname.split('?')[0];
    const params = new URLSearchParams(location.search);
    params.set('eventIds', newSelectedIds.join(','));
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
      : selectedEvents.length > 0 ? `${selectedEvents.length} events selected` : 'Select Event';

  if (compact) {
    const disabled = selectedEventIdsState.length >= MAX_SELECTED_EVENTS;

    return (
      <div className="relative">
        <button
          type="button"
          aria-label="Select events"
          aria-haspopup="listbox"
          aria-expanded={isOpen}
          onClick={() => setIsOpen((open) => !open)}
          className="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm hover:bg-slate-50 transition-colors ${
            disabled ? 'opacity-50 cursor-not-allowed' : ''
          }"
          disabled={disabled}
        >
          <span className={`w-2 h-2 rounded-full ${selectedEvents.length > 0 ? getStatusColor(selectedEvents[0].status) : 'bg-slate-300'}`} />
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
                        {recentEvents.map((eventId) => {
                          const event = events.find((e) => String(e.id) === eventId);
                          if (!event) return null;
                          return (
                            <button
                              key={`recent-${event.id}`}
                              type="button"
                              onClick={() => handleSelect(event.id)}
                              className={`w-full flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors text-left ${
                                String(event.id) === String(selectedEventIdsState[0]) ? 'bg-indigo-50' : ''
                              }`}
                            >
                              <span className="text-sm">🕐</span>
                              <div className="flex-1 min-w-0">
                                <div className="text-sm font-medium text-slate-800 truncate">{event.name}</div>
                                <div className="text-xs text-slate-500">{event.date}</div>
                              </div>
                            </button>
                          );
                        })}
                      </div>
                      <div className="border-t border-slate-100" />
                    </>
                  )}
                  <div className="p-2 border-b border-slate-100">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider px-2">All Events</span>
                  </div>
                  <div className="max-h-64 overflow-y-auto">
                    {remainingEvents.length === 0 && (
                      <div className="p-4 text-sm text-slate-500">No events available.</div>
                    )}
                    {remainingEvents.map((event) => {
                      const isSelected = selectedEventIdsState.includes(event.id);
                      const disabled = selectedEventIdsState.length >= MAX_SELECTED_EVENTS && !isSelected;
                      return (
                        <button
                          key={event.id}
                          type="button"
                          role="option"
                          aria-selected={isSelected}
                          onClick={() => handleSelect(event.id)}
                          className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors text-left ${
                            isSelected ? 'bg-indigo-50' : ''
                          }`}
                          disabled={disabled}
                        >
                          <span className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${getStatusColor(event.status)}`} />
                          <div className="flex-1 min-w-0">
                            <div className="text-sm font-medium text-slate-800 truncate">{event.name}</div>
                            <div className="text-xs text-slate-500">{event.date}</div>
                          </div>
                          {disabled && (
                            <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-200 text-slate-500">
                              Max 3 events
                            </span>
                          )}
                        </button>
                      );
                    })}
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
          Current Events
        </label>
      )}
      <div className="flex items-center gap-3">
        <div className="flex-1">
          <select
            aria-label="Select events"
            value={selectedEventIdsState.length > 0 ? selectedEventIdsState.join(',') : ''}
            onChange={(e) => {
              const ids = e.target.value.split(',').filter(Boolean).map(Number);
              setSelectedEventIds(ids);
              if (onSelect) onSelect(e.target.value);
            }}
            disabled={loading || Boolean(error)}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-slate-50 disabled:text-slate-400"
          >
            {loading && <option value="">Loading events…</option>}
            {!loading && error && <option value="">Failed to load events</option>}
            {!loading && !error && (
              <>
                <option value="">Select events...</option>
                {events.map((event) => {
                  const isSelected = selectedEventIdsState.includes(event.id);
                  const disabled = selectedEventIdsState.length >= MAX_SELECTED_EVENTS && !isSelected;
                  return (
                    <option
                      key={event.id}
                      value={event.id}
                      disabled={disabled}
                    >
                      {event.name} ({event.date}) {disabled && '—'}
                    </option>
                  );
                })}
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
        {selectedEvents.length > 0 && !error && (
          <span className={`px-2.5 py-1 rounded-full text-xs font-bold ${
            selectedEvents[0].status === 'active' ? 'bg-emerald-100 text-emerald-700' :
            selectedEvents[0].status === 'upcoming' ? 'bg-amber-100 text-amber-700' :
            'bg-slate-100 text-slate-600'
          }`}>
            {selectedEvents.length === 1 ? getStatusLabel(selectedEvents[0].status) : `${selectedEvents.length} events selected`}
          </span>
        )}
      </div>
    </div>
  );
};

export default EventSelector;