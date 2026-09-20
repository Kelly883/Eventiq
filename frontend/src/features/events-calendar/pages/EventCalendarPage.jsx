import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { api } from '../../../lib/api';
import CalendarGrid from '../components/calendar/CalendarGrid';
import CalendarDayDetailModal from '../pages/CalendarDayDetailModal';
import '../../../styles/shared-pages.css';

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
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">Event Calendar</h1>
        </div>

        {/* Calendar with date selection */}
        <div style={{ marginBottom: '24px' }}>
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
          <div style={{ marginTop: '24px' }}>
            <Link
              to="/organizer/events/create"
              className="spa-btn spa-btn--primary"
            >
              ✨ Create Event
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default EventCalendarPage;
