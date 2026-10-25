import React, { useState } from 'react';

import EventDetailWidget from './EventDetailWidget';

export interface DashboardEventCardEventData {
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

export interface DashboardEventCardProps {
  event: DashboardEventCardEventData;
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

const formatDate = (value?: string): string => {
  if (!value) return 'Date TBA';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return new Intl.DateTimeFormat('en-GB', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(parsed);
};

const getStatusBadge = (status?: string) => {
  const normalized = (status || 'active').toLowerCase();
  const classes = STATUS_COLORS[normalized] || STATUS_COLORS.active;
  return (
    <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border shadow-sm ${classes}`}>
      {normalized.charAt(0).toUpperCase() + normalized.slice(1)}
    </span>
  );
};

const SkeletonCard = () => (
  <div className="border border-slate-200 rounded-xl overflow-hidden bg-white animate-pulse" aria-hidden="true">
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

const EventBanner = ({ title, bannerUrl }: { title: string; bannerUrl?: string }) => {
  const [failed, setFailed] = useState(false);
  const showImage = Boolean(bannerUrl) && !failed;

  return (
    <div className="absolute inset-0 overflow-hidden rounded-t-xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-slate-700">
      {showImage ? (
        <img
          src={bannerUrl}
          alt={title}
          className="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          loading="lazy"
          onError={() => setFailed(true)}
        />
      ) : (
        <div className="absolute inset-0 flex items-center justify-center" role="presentation">
          <svg className="w-14 h-14 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={1.5}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>
      )}
      <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-transparent" />
    </div>
  );
};

export const DashboardEventCard = React.memo(function DashboardEventCard({
  event,
  isExpanded,
  onToggle,
  onViewAnalytics,
  onManageInventory,
  onEditEvent,
}: DashboardEventCardProps) {
  const rawProgress = event.progressPercent ??
    (event.ticketsTotal ? Math.round(((event.ticketsSold ?? 0) / event.ticketsTotal) * 100) : 0);
  const progress = Number.isFinite(rawProgress) ? Math.min(100, Math.max(0, rawProgress)) : 0;

  const revenue = event.revenue ?? 0;
  const attendees = event.attendeeCount ?? event.ticketsSold ?? 0;

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onToggle();
    }
  };

  return (
    <div
      className={`group border rounded-xl overflow-hidden transition-all duration-200 ${
        isExpanded
          ? 'border-indigo-300 bg-indigo-50/30 shadow-md'
          : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'
      }`}
    >
      <div className="relative h-40">
        <EventBanner title={event.title} bannerUrl={event.bannerUrl} />
        <div className="absolute top-3 right-3">{getStatusBadge(event.status)}</div>
        <div className="absolute bottom-3 left-3 right-3">
          <h3 className="text-white text-lg font-bold truncate" title={event.title}>
            {event.title}
          </h3>
          <p className="text-white/85 text-sm mt-0.5 truncate" title={event.organizerName || ''}>
            {event.organizerName || 'Unknown Organizer'}
          </p>
        </div>
      </div>

      <div className="p-4">
        <button
          onClick={onToggle}
          onKeyDown={handleKeyDown}
          className="w-full text-left flex items-start justify-between gap-3 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-md"
          aria-expanded={isExpanded}
          aria-controls={`event-details-${event.id}`}
        >
          <div className="min-w-0 flex-1">
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Event ID: {event.id}</p>
            <p className="font-bold text-slate-800 text-sm truncate mt-0.5" title={event.title}>
              {event.title}
            </p>
            <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
              <span className="inline-flex items-center gap-1">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{formatDate(event.startDate)}</span>
              </span>
              <span className="inline-flex items-center gap-1 min-w-0">
                <svg className="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.315 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span className="truncate">{event.venueName || 'Venue TBA'}</span>
              </span>
            </div>
          </div>
          <span
            className={`inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-md transition-colors flex-shrink-0 ${
              isExpanded ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'
            }`}
          >
            {isExpanded ? 'Hide' : 'Details'}
            <svg
              className={`w-3.5 h-3.5 transition-transform duration-200 ${isExpanded ? 'rotate-180' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
            </svg>
          </span>
        </button>

        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div className="bg-slate-50 rounded-lg p-3 border border-slate-100 min-w-0">
            <p className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Revenue</p>
            <p className="text-lg font-bold text-slate-800 truncate" title={formatCurrency(revenue)}>
              {formatCurrency(revenue)}
            </p>
          </div>
          <div className="bg-slate-50 rounded-lg p-3 border border-slate-100 min-w-0">
            <p className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Attendees</p>
            <p className="text-lg font-bold text-slate-800 truncate">{formatNumber(attendees)}</p>
          </div>
          <div className="bg-slate-50 rounded-lg p-3 border border-slate-100 col-span-2 sm:col-span-1 min-w-0">
            <div className="flex items-baseline justify-between gap-2">
              <p className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Progress</p>
              <p className="text-xs font-bold text-slate-600">{progress}%</p>
            </div>
            <div
              className="h-2 bg-slate-200 rounded-full overflow-hidden mt-2"
              role="progressbar"
              aria-valuenow={progress}
              aria-valuemin={0}
              aria-valuemax={100}
              aria-label={`Tickets sold for ${event.title}`}
            >
              <div
                className={`h-full rounded-full transition-all duration-500 ${
                  progress >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'
                }`}
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
        </div>

        {event.desc && !isExpanded && (
          <p className="text-sm text-slate-500 mt-3 line-clamp-2 leading-relaxed">{event.desc}</p>
        )}

        <div className="mt-4 flex flex-wrap gap-2">
          {onViewAnalytics && (
            <button
              onClick={onViewAnalytics}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
              aria-label="View analytics"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              View Analytics
            </button>
          )}
          {onManageInventory && (
            <button
              onClick={onManageInventory}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
              aria-label="Manage inventory"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              Manage Inventory
            </button>
          )}
          {onEditEvent && (
            <button
              onClick={onEditEvent}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
              aria-label="Edit event"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Edit Event
            </button>
          )}
        </div>
      </div>

      {isExpanded && (
        <div id={`event-details-${event.id}`} className="px-4 pb-4">
          <EventDetailWidget
            eventId={event.id}
            onViewAnalytics={onViewAnalytics}
            onManageInventory={onManageInventory}
            onEditEvent={onEditEvent}
          />
        </div>
      )}
    </div>
  );
});

DashboardEventCard.displayName = 'DashboardEventCard';

export { SkeletonCard as DashboardEventCardSkeleton };
