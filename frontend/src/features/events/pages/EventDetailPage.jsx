import React, { useEffect, useState } from 'react';
import { Link, useParams, useLocation, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import Breadcrumb from '../../components/Breadcrumb';

const EventDetailPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuthContext();
  const [event, setEvent] = useState(null);
  const [breadcrumb, setBreadcrumb] = useState(['Events']);
  const [relatedEvents, setRelatedEvents] = useState<readonly any[]>([]);
  const [isLoadingRelated, setIsLoadingRelated] = useState(false);

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

  // Fetch related events
  useEffect(() => {
    const loadRelatedEvents = async () => {
      if (!event) return;
      setIsLoadingRelated(true);
      try {
        const res = await api.get(`/events/${eventId}/related`, {
          params: {
            limit: 4,
            category: event.category,
            organizerId: event.organizerId,
          },
        });
        setRelatedEvents(res.data.data || []);
      } catch (err) {
        console.error('Failed to fetch related events', err);
        setRelatedEvents([]);
      } finally {
        setIsLoadingRelated(false);
      }
    };
    loadRelatedEvents();
  }, [eventId, event]);

        {/* Breadcrumb */}
        <Breadcrumb
          items={[{ label: 'Events', href: '/events' }]}
          currentItem={{ label: event?.name || event?.title || `Event #${eventId}` }}
          separator='>'
        />

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
          {/* Share Button */}
          {event && (
            <div className="mb-4 flex items-center gap-2">
              <button
                onClick={(e) => {
                  e.preventDefault();
                  if (navigator.share) {
                    navigator.share({
                      title: event.name || event.title || 'Event',
                      text: event.description || 'Check out this event!',
                      url: window.location.href,
                    });
                  } else {
                    navigator.clipboard.writeText(window.location.href).then(() => {
                      alert('Link copied to clipboard!');
                    });
                  }
                }}
                className="flex items-center gap-1 px-3 py-1.5 rounded-md text-sm text-primary-600 hover:bg-primary-100 transition-colors"
                aria-label="Share event"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Share
              </button>
            </div>
          )}

          {/* Related Events Section */}
          {relatedEvents.length > 0 && !isLoadingRelated && (
            <div className="mt-8 pt-8 border-t border-slate-200">
              <h2 className="text-xl font-bold text-slate-800 mb-4">You Might Also Like</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {relatedEvents.map((relatedEvent) => (
                  <div
                    key={relatedEvent.id}
                    className="bg-white rounded-xl border border-slate-200 hover:border-primary-200 transition-colors p-4 min-h-[280px]"
                  >
                    <h3 className="text-sm font-medium text-slate-800 mb-2 truncate">
                      {relatedEvent.name || relatedEvent.title}
                    </h3>
                    <p className="text-xs text-slate-500 line-clamp-2">
                      {relatedEvent.description?.substring(0, 100) || ''}...
                    </p>
                    {relatedEvent.ticketTiers?.[0] && (
                      <p className="text-xs text-primary-600 mt-1">
                        from {relatedEvent.ticketTiers[0].price || '0.00'}
                      </p>
                    )}
                  </div>
                ))}
                {isLoadingRelated && (
                  <p className="text-sm text-slate-400 text-center">Loading more events...</p>
                )}
              </div>
            </div>
          )}

          {relatedEvents.length === 0 && !isLoadingRelated && (
            <p className="text-sm text-slate-400 mt-2">No related events found.</p>
          )}

          {event ? null : (
            <div className="text-center py-12">
              <h2 className="text-xl font-bold text-slate-900">Event Not Found</h2>
              <p className="text-slate-500">The event you requested does not exist.</p>
              <Link
                to="/events"
                className="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
              >
                Go to Event Browse
              </Link>
            </div>
          )}
        )}
      </div>
    </div>
  );
};

export default EventDetailPage;
