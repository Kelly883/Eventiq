import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { organizerService } from '../services';

const OrganizerPublicEventsPage = () => {
  const { organizerId } = useParams();
  const navigate = useNavigate();
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchEvents = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = await organizerService.getPublicEvents(organizerId);
        setEvents(data.data || []);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to load events');
      } finally {
        setLoading(false);
      }
    };

    fetchEvents();
  }, [organizerId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-slate-500 font-semibold">Loading events...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center gap-4">
        <div className="text-rose-600 font-semibold">{error}</div>
        <button
          onClick={() => navigate(-1)}
          className="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300 transition-colors"
        >
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors"
        >
          ← Back
        </button>

        <div className="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
          <h1 className="text-2xl font-extrabold text-slate-900 mb-6">Events</h1>

          {events.length === 0 ? (
            <p className="text-slate-500 text-sm">No events found.</p>
          ) : (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {events.map((event) => (
                <Link
                  key={event.id}
                  to={`/events/${event.id}`}
                  className="border border-slate-100 rounded-xl p-5 hover:border-indigo-200 hover:shadow-md transition-all"
                >
                  <h3 className="font-bold text-slate-900 text-sm mb-1 line-clamp-2">{event.title}</h3>
                  <p className="text-xs text-slate-500 line-clamp-2">{event.description}</p>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default OrganizerPublicEventsPage;
