import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { api } from '../../../lib/api';
import CalendarGrid from '../components/calendar/CalendarGrid';
import CalendarDayDetailModal from '../pages/CalendarDayDetailModal';

const EventCalendarPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuthContext();
  const [events, setEvents] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);

  // Persist selected date via URL query param ?selectedDate=YYYY-MM-DD
  useEffect(() => {
    const urlParams = new URLSearchParams(location.search);
    const urlDate = urlParams.get('selectedDate');
    if (urlDate) {
      setSelectedDate(urlDate);
    }
  }, [location.search]);

  // Update URL when selected date changes
  useEffect(() => {
    if (selectedDate) {
      const params = new URLSearchParams(location.search);
      params.set('selectedDate', selectedDate);
      const newUrl = `${location.pathname}?${params.toString()}`;
      navigate(newUrl, { replace: true });
    }
  }, [selectedDate, navigate, location]);

  // Fetch events on mount
  useEffect(() => {
    async function fetchEvents() {
      try {
        const res = await api.get('/calendar');
        setEvents(res.data.data || []);
      } catch (err) {
        console.error('Failed to fetch events', err);
        setEvents([]);
      }
    }
    fetchEvents();
  }, []);

  // Check if user is organizer for Create Event button
  const isOrganizer = user && user.roles && user.roles.some((r) => r.name === 'organizer');

  // Handle event selection - navigate to event detail page
  const handleSelectEvent = (event) => {
    navigate(`/events/${event.id}`);
  };

  return (
    <div className="p-6">
      <h1 className="text-xl font-bold mb-4">Event Calendar</h1>

      {/* Calendar with date selection */}
      <div className="mb-6">
        <CalendarGrid
          events={events}
          onSelectDate={setSelectedDate}
          onSelectEvent={handleSelectEvent}
          defaultView="month"
        />
      </div>

      {/* Selected date details */}
      {selectedDate && (
        <CalendarDayDetailModal
          selectedDate={selectedDate}
          events={events}
        />
      )}

      {/* Create Event button - visible only to organizers */}
      {isOrganizer && (
        <div className="mt-6">
          <Link
            to="/organizer/events/create"
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
          >
            ✨ Create Event
          </Link>
        </div>
      )}
    </div>
  );
};

export default EventCalendarPage;
