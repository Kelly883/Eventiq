import React from 'react';
import { Link } from 'react-router-dom';

interface EventData {
  id: string | number;
  title: string;
  desc?: string;
  bannerUrl?: string;
  status?: string;
  startDate?: string;
  endDate?: string;
  venueName?: string;
  revenue?: number;
  attendeeCount?: number;
  ticketsSold?: number;
  ticketsTotal?: number;
  progressPercent?: number;
  organizerName?: string;
}

interface DashboardEventCardProps {
  event: EventData;
  isExpanded: boolean;
  onToggle: () => void;
  onViewAnalytics?: () => void;
  onManageInventory?: () => void;
  onEditEvent?: () => void;
}

const STATUS_COLORS: Record<string, string> = {
  active: 'bg-emerald-100 text-emerald-700 border-emerald-200',
  completed: 'bg-blue-100 text-blue-700 border-blue-200',
  cancelled: 'bg-rose-100 text-rose-700 border-rose-200',
  draft: 'bg-slate-100 text-slate-700 border-slate-200',
  pending: 'bg-amber-100 text-amber-700 border-amber-200',
};

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('en-US').format(value);
};

const getStatusBadge = (status?: string) => {
  const normalized = (status || 'active').toLowerCase();
  const classes = STATUS_COLORS[normalized] || STATUS_COLORS.active;
  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${classes}`}>
      {normalized.charAt(0).toUpperCase() + normalized.slice(1)}
    </span>
  );
};

const SkeletonCard = () => (
  <div className="border rounded-lg overflow-hidden bg-white animate-pulse">
    <div className="h-40 bg-slate-200" />
    <div className="p-4 space-y-3">
      <div className="h-4 w-3/4 bg-slate-200 rounded" />
      <div className="h-3 w-1/2 bg-slate-200 rounded" />
      <div className="grid grid-cols-3 gap-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="space-y-2">
            <div className="h-3 w-full bg-slate-200 rounded" />
            <div className="h-6 w-full bg-slate-200 rounded" />
          </div>
        ))}
      </div>
      <div className="h-8 w-24 bg-slate-200 rounded" />
    </div>
  </div>
);

export const DashboardEventCard = React.memo(function DashboardEventCard({
  event,
  isExpanded,
  onToggle,
  onViewAnalytics,
  onManageInventory,
  onEditEvent,
}: DashboardEventCardProps) {
  const progress = event.progressPercent ?? 
    (event.ticketsTotal && event.ticketsSold ? Math.round((event.ticketsSold / event.ticketsTotal) * 100) : 0);
  
  const revenue = event.revenue ?? 0;
  const attendees = event.attendeeCount ?? event.ticketsSold ?? 0;
  const ticketsSold = event.ticketsSold ?? 0;
  const ticketsTotal = event.ticketsTotal ?? 0;

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onToggle();
    }
  };

  return (
    <div className={`border rounded-lg overflow-hidden transition-all duration-200 ${
      isExpanded 
        ? 'border-indigo-200 bg-indigo-50/10 shadow-md' 
        : 'border-slate-100 bg-white hover:border-slate-200 hover:shadow-sm'
    }`}>
      <div className="relative h-40">
        <img
          src={event.bannerUrl || '/placeholder-event.jpg'}
          alt={event.title}
          className="absolute inset-0 w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
          loading="lazy"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/40 to-transparent" />
        <div className="absolute top-3 right-3">
          {getStatusBadge(event.status)}
        </div>
        <div className="absolute bottom-3 left-3 right-3">
          <h3 className="text-white text-lg font-bold truncate">{event.title}</h3>
          <p className="text-white/80 text-sm mt-0.5 truncate max-w-[140px]">
            {event.organizerName || 'Unknown Organizer'}
          </p>
        </div>
      </div>

      <button
        onClick={onToggle}
        onKeyDown={handleKeyDown}
        className="w-full text-left p-4 flex items-center justify-between transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        aria-expanded={isExpanded}
        aria-controls={`event-details-${event.id}`}
      >
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
            <svg className="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <div>
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Event ID: {event.id}</p>
            <p className="font-bold text-slate-800 text-sm truncate max-w-xs">{event.title}</p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <span className={`text-xs px-2.5 py-1 rounded-md font-medium transition-all ${
            isExpanded 
              ? 'bg-indigo-600 text-white' 
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}>
            {isExpanded ? 'Collapse' : 'Expand'}
          </span>
          <svg
            className={`w-5 h-5 text-slate-400 transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`}
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </button>

      {isExpanded && (
        <div 
          id={`event-details-${event.id}`}
          className="p-4 border-t border-slate-100 bg-slate-50/50 animate-fadeIn"
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div className="bg-white p-4 rounded-lg border border-slate-100">
              <p className="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Revenue</p>
              <p className="text-2xl font-bold text-slate-800">{formatCurrency(revenue)}</p>
            </div>
            <div className="bg-white p-4 rounded-lg border border-slate-100">
              <p className="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Attendees</p>
              <p className="text-2xl font-bold text-slate-800">{formatNumber(attendees)}</p>
            </div>
            <div className="bg-white p-4 rounded-lg border border-slate-100">
              <p className="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Tickets Sold</p>
              <p className="text-2xl font-bold text-slate-800">{formatNumber(ticketsSold)} / {formatNumber(ticketsTotal)}</p>
            </div>
            <div className="bg-white p-4 rounded-lg border border-slate-100">
              <p className="text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Capacity</p>
              <div className="h-2 bg-slate-200 rounded-full overflow-hidden">
                <div 
                  className="h-full bg-indigo-600 rounded-full transition-all duration-500"
                  style={{ width: `${Math.min(progress, 100)}%` }}
                />
              </div>
              <p className="text-xs text-slate-500 mt-1 text-right">{progress}% filled</p>
            </div>
          </div>

          {event.desc && (
            <p className="text-sm text-slate-600 mb-4 leading-relaxed">{event.desc}</p>
          )}

          <div className="flex flex-wrap gap-2">
            {onViewAnalytics && (
              <button
                onClick={onViewAnalytics}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                aria-label="View Full Analytics"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                View Analytics
              </button>
            )}
            {onManageInventory && (
              <button
                onClick={onManageInventory}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
                aria-label="Manage Inventory"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Manage Inventory
              </button>
            )}
            {onEditEvent && (
              <button
                onClick={onEditEvent}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
                aria-label="Edit Event"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Event
              </button>
            )}
          </div>
        </div>
      )}
    </div>
  );
});

DashboardEventCard.displayName = 'DashboardEventCard';

export { SkeletonCard as DashboardEventCardSkeleton };