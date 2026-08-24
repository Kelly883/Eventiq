import React from 'react';
import { Link, useParams } from 'react-router-dom';

const EventPricingConfigPage = () => {
  const { eventId } = useParams();
  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
      <div className="mx-auto max-w-4xl">
        <Link to="/organizer/events" className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3">
          ← Back to Events
        </Link>
        <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
          Pricing Configuration
        </h1>
        <p className="mt-1 text-sm text-[#999999] mb-6">Event ID: {eventId} • Windows, early-bird & rules</p>

        <div className="bg-white rounded-xl border border-[#E3E4E6] p-1.5 mb-6 shadow-sm flex flex-wrap gap-1">
          <Link to={`/organizer/events/${eventId}/ticketing`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            🎟️ Ticket Tiers
          </Link>
          <Link to={`/organizer/events/${eventId}/inventory`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            📦 Inventory
          </Link>
          <Link to={`/organizer/events/${eventId}/pricing`} className="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white shadow-sm" aria-current="page">
            💰 Pricing
          </Link>
          <Link to={`/organizer/events/${eventId}/edit`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            ✎ Edit Event
          </Link>
          <span className="ml-auto hidden md:inline-flex items-center text-xs text-[#999999] px-2">Pricing = windows & rules</span>
        </div>

        <div className="bg-white rounded-xl border border-[#E3E4E6] p-8 shadow-sm text-center">
          <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6]">💰</div>
          <h2 className="text-lg font-semibold text-[#333333]">Pricing windows coming soon</h2>
          <p className="mt-1 text-sm text-[#999999]">Configure early-bird, sales windows and tier pricing rules here.</p>
          <Link to={`/organizer/events/${eventId}/ticketing`} className="mt-4 inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">
            ← Back to Ticket Tiers
          </Link>
        </div>
      </div>
    </div>
  );
};

export default EventPricingConfigPage;
