import React from 'react';
import { Link } from 'react-router-dom';
import { useEventContext } from '../components/OrganizerEventLayout';

const OrganizerEventOverviewPage = () => {
  const { event, eventId } = useEventContext();

  return (
    <div>
      <div className="bg-white rounded-xl border border-[#E3E4E6] p-4 shadow-sm flex items-center justify-between">
        <div className="text-sm">
          <p className="font-semibold text-[#333333]">{event.title}</p>
          <p className="text-xs text-[#999999]">{event.venue_name || event.venueName || 'No venue'} • {event.start_date || event.startDate || ''}</p>
        </div>
        <span className={`px-2 py-1 rounded text-xs font-medium border ${event.status === 'draft' ? 'bg-[#FFDA6B]/30 border-[#FFDA6B]' : 'bg-[#4ECDC4]/10 border-[#4ECDC4]/20'}`}>{event.status || 'published'}</span>
      </div>

      <div className="mt-6 grid md:grid-cols-2 gap-4">
        <Link to={`/organizer/events/${eventId}/edit`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">✎</div>
          <h3 className="mt-3 text-sm font-semibold text-[#333333]">Edit Event</h3>
          <p className="mt-1 text-xs text-[#999999]">Update title, description, venue, capacity and visibility.</p>
          <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Open →</span>
        </Link>
        <Link to={`/organizer/events/${eventId}/ticketing`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">🎟️</div>
          <h3 className="mt-3 text-sm font-semibold text-[#333333]">Ticket Tiers</h3>
          <p className="mt-1 text-xs text-[#999999]">What you sell — tiers, prices, descriptions.</p>
          <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Manage →</span>
        </Link>
        <Link to={`/organizer/events/${eventId}/inventory`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">📦</div>
          <h3 className="mt-3 text-sm font-semibold text-[#333333]">Inventory</h3>
          <p className="mt-1 text-xs text-[#999999]">How many — remaining stock and adjustments.</p>
          <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Adjust →</span>
        </Link>
        <Link to={`/organizer/events/${eventId}/pricing`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">💰</div>
          <h3 className="mt-3 text-sm font-semibold text-[#333333]">Pricing Windows</h3>
          <p className="mt-1 text-xs text-[#999999]">When and how much — sales windows & rules. Preview how buyers see prices.</p>
          <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Configure →</span>
        </Link>
        <Link to={`/organizer/events/${eventId}/analytics`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">📈</div>
          <h3 className="mt-3 text-sm font-semibold text-[#333333]">Analytics</h3>
          <p className="mt-1 text-xs text-[#999999]">Sales velocity, conversion rates, and performance metrics.</p>
          <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">View →</span>
        </Link>
      </div>

      <p className="mt-4 text-center text-xs text-[#B3B3B3]">Deep link: /organizer/events/{eventId}</p>
    </div>
  );
};

export default OrganizerEventOverviewPage;
