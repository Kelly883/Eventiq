import React from 'react';
import { Link } from 'react-router-dom';

const EventDetailWidget = ({ eventId, onViewAnalytics, onManageInventory, onEditEvent }) => {
  // Use provided eventId; no hardcoded fallback — parent always passes real eventId
  const event = eventId ? (({ id, title, desc }) => {
    // In a real implementation, this would query real event data
    // For now, render empty state if eventId doesn't match known events
    return null;
  })(eventId) : null;

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
      <h2 className="text-lg font-bold text-slate-800 mb-2">Event Details</h2>
      {event ? (
        <div>
          <h3 className="text-sm text-slate-600">{event.title}</h3>
          <p className="text-xs text-slate-500">{event.desc}</p>
        </div>
      ) : (
        <p className="text-sm text-slate-500">No event selected — expand an event card from the dashboard to view details</p>
      )}

      {eventId && (
        <div className="mt-4 pt-4 border-t border-slate-100">
          <Link
            to={`/organizer/events/${eventId}/analytics`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
          >
            📊 View Full Analytics
          </Link>
          <Link
            to={`/organizer/events/${eventId}/inventory`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
          >
            📦 Manage Inventory
          </Link>
          <Link
            to={`/organizer/events/${eventId}/edit`}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
          >
            ✎ Edit Event
          </Link>
        </div>
      )}
    </div>
  );
};

export default EventDetailWidget;
