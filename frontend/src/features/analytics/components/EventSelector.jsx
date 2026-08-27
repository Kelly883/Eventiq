import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';

const mockEvents = [
  { id: 1, name: 'Summer Music Festival 2026', date: '2026-08-28', status: 'active' },
  { id: 2, name: 'Tech Conference 2026', date: '2026-09-15', status: 'active' },
  { id: 3, name: 'Food & Wine Expo', date: '2026-10-01', status: 'upcoming' },
  { id: 4, name: 'Charity Gala Night', date: '2026-07-20', status: 'ended' },
];

const RECENT_EVENTS_KEY = 'eventiq_recent_events';
const MAX_RECENT_EVENTS = 3;

const getRecentEvents = () => {
  try {
    const stored = localStorage.getItem(RECENT_EVENTS_KEY);
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
};

const addRecentEvent = (eventId) => {
  const recent = getRecentEvents();
  const filtered = recent.filter((id) => id !== eventId);
  const updated = [eventId, ...filtered].slice(0, MAX_RECENT_EVENTS);
  localStorage.setItem(RECENT_EVENTS_KEY, JSON.stringify(updated));
};

const EventSelector = ({ compact = false, showLabel = true }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const [selectedEventId, setSelectedEventId] = useState(null);
  const [isOpen, setIsOpen] = useState(false);
  const [recentEvents, setRecentEvents] = useState([]);

  const selectedEvent = mockEvents.find(e => e.id === selectedEventId);

  useEffect(() => {
    setRecentEvents(getRecentEvents());
  }, []);

  const handleSelect = (eventId) => {
    setSelectedEventId(eventId);
    setIsOpen(false);
    addRecentEvent(eventId);
    setRecentEvents(getRecentEvents());

    const currentPath = location.pathname;
    if (currentPath.includes('/venue/check-in/')) {
      navigate(`/venue/check-in/${eventId}`);
    } else if (currentPath.includes('/check-in')) {
      const base = currentPath.split('?')[0];
      const params = new URLSearchParams(location.search);
      params.set('eventId', String(eventId));
      navigate(`${base}?${params.toString()}`);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'active': return 'bg-emerald-500';
      case 'upcoming': return 'bg-amber-500';
      case 'ended': return 'bg-slate-400';
      default: return 'bg-slate-400';
    }
  };

  const getStatusLabel = (status) => {
    switch (status) {
      case 'active': return 'Live';
      case 'upcoming': return 'Upcoming';
      case 'ended': return 'Ended';
      default: return status;
    }
  };

  if (compact) {
    return (
      <div className="relative">
        <button
          onClick={() => setIsOpen(!isOpen)}
          className="flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm hover:bg-slate-50 transition-colors"
        >
          <span className={`w-2 h-2 rounded-full ${selectedEvent ? getStatusColor(selectedEvent.status) : 'bg-slate-300'}`} />
          <span className="text-sm font-medium text-slate-700 truncate max-w-[150px]">
            {selectedEvent?.name || 'Select Event'}
          </span>
          <svg className={`w-4 h-4 text-slate-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        {isOpen && (
          <div className="absolute top-full left-0 mt-1 w-72 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
            {recentEvents.length > 0 && (
              <>
                <div className="p-2 border-b border-slate-100">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider px-2">Recent Events</span>
                </div>
                <div className="max-h-32 overflow-y-auto">
                  {recentEvents.map((eventId) => {
                    const event = mockEvents.find(e => e.id === eventId);
                    if (!event) return null;
                    return (
                      <button
                        key={`recent-${event.id}`}
                        onClick={() => handleSelect(event.id)}
                        className={`w-full flex items-center gap-3 px-4 py-2 hover:bg-slate-50 transition-colors text-left ${
                          selectedEventId === event.id ? 'bg-indigo-50' : ''
                        }`}
                      >
                        <span className="text-sm">🕐</span>
                        <div className="flex-1 min-w-0">
                          <div className="text-sm font-medium text-slate-800 truncate">{event.name}</div>
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
              {mockEvents.map((event) => (
                <button
                  key={event.id}
                  onClick={() => handleSelect(event.id)}
                  className={`w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors text-left ${
                    selectedEventId === event.id ? 'bg-indigo-50' : ''
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
          </div>
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
            value={selectedEventId || ''}
            onChange={(e) => handleSelect(Number(e.target.value))}
            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          >
            <option value="">Select an event...</option>
            {mockEvents.map((event) => (
              <option key={event.id} value={event.id}>
                {event.name} ({event.date})
              </option>
            ))}
          </select>
        </div>
        {selectedEvent && (
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
