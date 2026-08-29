import React, { useEffect, useState } from 'react';
import { Link, useParams, useLocation, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';

const EventDetailPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuthContext();
  const [event, setEvent] = useState(null);
  const [breadcrumb, setBreadcrumb] = useState(['Events']);

  // Fetch event data on mount
  useEffect(() => {
    async function fetchEvent() {
      try {
        const res = await api.get(`/events/${eventId}`);
        setEvent(res.data.data || null);
        // Set breadcrumb: 'Events > Event Title'
        if (res.data.data) {
          setBreadcrumb(['Events', res.data.data.name || res.data.data.title || `Event #${eventId}`]);
        }
      } catch (err) {
        console.error('Failed to fetch event', err);
        setEvent(null);
      }
    }
    fetchEvent();
  }, [eventId]);

  // Update breadcrumb when navigating from calendar/detail
  useEffect(() => {
    // If coming from calendar detail, breadcrumb is already set
    // Otherwise, ensure default breadcrumb state
    if (!breadcrumb[1]) {
      setBreadcrumb(['Events']);
    }
  }, [eventId, breadcrumb]);

  // Update breadcrumb when category filter changes via URL
  useEffect(() => {
    const urlParams = new URLSearchParams(location.search);
    const catId = urlParams.get('category');
    if (catId) {
      setBreadcrumb(['Events', 'Category']);
    } else if (event) {
      setBreadcrumb(['Events', event.name || event.title || `Event #${eventId}`]);
    }
  }, [eventId, location, event]);

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">

        {/* Breadcrumb Navigation */}
        <nav className="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
          <ol className="flex flex-wrap items-center gap-2">
{breadcrumb.map((segment, index) => {
              const isLast = index === breadcrumb.length - 1;
              return (
                <li key={index} className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 transition-colors">
                  {isLast ? (
                    <span>{segment}</span>
                  ) : (
                    <Link
                      to={index === 0 ? '/events' : '/events'}
                      className="underline cursor-pointer text-indigo-600"
                    >
                      {segment}
                    </Link>
                  )}
                </li>
              );
            })}
          </ol>
        </nav>

        {/* Navigation aids */}
        {breadcrumb.length >= 2 && (
          <div className="mb-4 text-sm text-slate-500">
            <Link
              to="/events/calendar"
              className="font-medium text-indigo-600 underline cursor-pointer"
            >
              ← Back to Calendar
            </Link>
            <span className="mx-2">|</span>
            <Link
              to="/events"
              className="font-medium text-indigo-600 underline cursor-pointer"
            >
              ← Back to Events
            </Link>
          </div>
        )}

        {/* Event Details */}
        {event ? (
          <div>
            <h1 className="text-2xl font-bold text-slate-900 mb-4">{event.name || event.title || `Event #${eventId}`}</h1>
            <p className="text-sm text-slate-500 mb-6">{event.description || 'No description available'}</p>

            {/* Ticket Tiers */}
            <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
              <h2 className="text-lg font-bold text-slate-800 mb-4">Ticket Tiers</h2>
              {event.ticketTiers && event.ticketTiers.length > 0 ? (
                <ul className="space-y-3">
                  {event.ticketTiers.map((tier) => (
                    <li key={tier.id} className="text-sm text-slate-600">
                      <strong>{tier.name || 'General Admission'}</strong>: ${tier.price || '0.00'} — {tier.availableTickets || tier.totalTickets} tickets available
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-sm text-slate-500">No ticket tiers available</p>
              )}
            </div>

            {/* Pricing Information */}
            {event.pricingWindows && event.pricingWindows.length > 0 ? (
              <div className="mt-6">
                <h3 className="text-lg font-bold text-slate-800 mb-3">Pricing Windows</h3>
                <p className="text-sm text-slate-500">{event.pricingWindows.map((w) => `${w.name}: $${w.price}`).join(', ')}</p>
              </div>
            ) : null}
          </div>
        ) : (
          <div className="text-center py-12">
            <h2 className="text-xl font-bold text-slate-900">Event Not Found</h2>
            <p className="text-slate-500">The event you requested does not exist.</p>
            <Link to="/events" className="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700">
              Go to Event Browse
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default EventDetailPage;
