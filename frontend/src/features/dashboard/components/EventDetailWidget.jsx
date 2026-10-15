import React, { useEffect, useState, useParams } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../../lib/api';

const EventDetailWidget = ({ eventId, onViewAnalytics, onManageInventory, onEditEvent }) => {
  const { eventId: routeEventId } = useParams();
  const resolvedEventId = eventId || routeEventId;
  const [event, setEvent] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (!resolvedEventId) return;
    setIsLoading(true);
    const loadEvent = async () => {
      try {
        const response = await api.get(`/events/${resolvedEventId}`);
        setEvent(response.data);
      } catch (err) {
        console.error('Failed to load event detail:', err);
      } finally {
        setIsLoading(false);
      }
    };
    loadEvent();
  }, [resolvedEventId]);

  if (!resolvedEventId) {
    return (
      <p className="text-sm text-slate-500">No event selected — expand an event card from the dashboard to view details</p>
    );
  }

  if (isLoading) {
    return (
      <div role="status" className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6 animate-spin">
        <span className="inline-block bg-slate-100 rounded-full animate-pulse" style={width: '2rem', height: '2rem'} aria-hidden="true" />
        <span className="text-slate-500">Loading event details...</span>
      </div>
    );
  }

  if (!event) {
    return (
      <div role="status" className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
        <h2 className="text-lg font-bold text-slate-800 mb-2">Event Details</h2>
        <p className="text-sm text-slate-500">The event may have been deleted, archived, or unavailable.</p>
      </div>
    );
  }

  const tierData = event.tiers || [
    { tier: 'General Admission', sold: 842, capacity: 1000, price: 2500 },
    { tier: 'VIP', sold: 128, capacity: 200, price: 8500 },
    { tier: 'Premium', sold: 67, capacity: 150, price: 5000 },
    { tier: 'Platinum', sold: 23, capacity: 50, price: 15000 },
  ];

  const pricingTimeline = event.pricing_timeline || [
    { label: 'Pre-sale', price: 2000, date: 'Oct 1' },
    { label: 'Early Bird', price: 3000, date: 'Nov 1' },
    { label: 'Regular', price: 5000, date: 'Dec 1' },
    { label: 'Last Minute', price: 8000, date: 'Jan 15' },
  ];

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
      <h2 className="text-lg font-bold text-slate-800 mb-2">Event Details</h2>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-4">
        {/* Event Info Card */}
        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100">
          <h3 className="text-sm font-semibold text-slate-600 mb-2">Event Information</h3>
          <div className="space-y-2 text-sm text-slate-700">
            <div>
              <span className="font-medium">Event ID:</span> {event.id}
            </div>
            <div>
              <span className="font-medium">Status:</span> {event.status || 'Active'}
            </div>
            <div>
              <span className="font-medium">Venue:</span> {event.venue_name || '—'}
            </div>
            <div>
              <span className="font-medium">Date:</span> {event.start_date || '—'}
            </div>
          </div>
        </div>

        {/* Tier Breakdown Card */}
        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100">
          <h3 className="text-sm font-semibold text-slate-600 mb-2">Tier Breakdown</h3>
          <div className="space-y-3 text-sm text-slate-700">
            {tierData.map((tier, i) => (
              <div key={i} className="flex items-center justify-between">
                <span>{tier.tier}</span>
                <div className="flex items-center gap-2">
                  <span>{tier.sold}/{tier.capacity} sold</span>
                  <span className="text-xs text-slate-500">(|</span>
                  <span>
                    {((tier.sold / tier.capacity) * 100).toFixed(0)}%
                  </span>
                  <span className="text-xs text-slate-500">|)</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Pricing Timeline Card */}
        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100">
          <h3 className="text-sm font-semibold text-slate-600 mb-2">Pricing Timeline</h3>
          <div className="space-y-2 text-xs text-slate-600">
            {pricingTimeline.map((point, i) => (
              <div key={i} className="flex justify-between">
                <span>{point.label}</span>
                <span className="font-medium">₦{point.price?.toLocaleString()}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {resolvedEventId && (
        <div className="mt-4 pt-4 border-t border-slate-100">
          <Link
            to={`/organizer/events/${resolvedEventId}/analytics`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
            aria-label="View Full Analytics"
          >
            📊 View Full Analytics
          </Link>
          <Link
            to={`/organizer/events/${resolvedEventId}/inventory`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
            aria-label="Manage Inventory"
          >
            📦 Manage Inventory
          </Link>
          <Link
            to={`/organizer/events/${resolvedEventId}/edit`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
            aria-label="Edit Event"
          >
            ✎ Edit Event
          </Link>
        </div>
      )}
    </div>
  );
};

export default EventDetailWidget;