import React from 'react';
import { useParams } from 'react-router-dom';
import { useEventDetail } from '../hooks/useEvents';
import { ErrorBoundary } from './ErrorBoundary';

interface TierRow {
  tier: string;
  sold: number;
  capacity: number;
  price: number;
}

interface PricingPoint {
  label: string;
  price: number;
  date: string;
}

const SkeletonInfoCard = () => (
  <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 animate-pulse">
    <div className="h-4 w-1/3 bg-slate-200 rounded mb-3" />
    <div className="space-y-2">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="h-4 w-full bg-slate-200 rounded" />
      ))}
    </div>
  </div>
);

const SkeletonTierCard = () => (
  <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 animate-pulse">
    <div className="h-4 w-1/3 bg-slate-200 rounded mb-3" />
    <div className="space-y-3">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="flex items-center justify-between">
          <div className="h-4 w-1/3 bg-slate-200 rounded" />
          <div className="flex items-center gap-2">
            <div className="h-4 w-16 bg-slate-200 rounded" />
            <div className="h-4 w-12 bg-slate-200 rounded" />
          </div>
        </div>
      ))}
    </div>
  </div>
);

const SkeletonPricingCard = () => (
  <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 animate-pulse">
    <div className="h-4 w-1/3 bg-slate-200 rounded mb-3" />
    <div className="space-y-2">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="flex justify-between">
          <div className="h-3 w-1/4 bg-slate-200 rounded" />
          <div className="h-4 w-20 bg-slate-200 rounded" />
        </div>
      ))}
    </div>
  </div>
);

const EmptyBlock = ({ children }: { children: React.ReactNode }) => (
  <p className="text-xs text-slate-400 italic">{children}</p>
);

const EmptyEventState = () => (
  <div role="status" className="bg-white rounded-lg border border-slate-200 p-4">
    <h3 className="text-sm font-semibold text-slate-700 mb-1">Event Details</h3>
    <p className="text-sm text-slate-500">
      This event is no longer available. It may have been deleted, archived, or you may not have access to it.
    </p>
  </div>
);

const EventDetailWidgetContent = ({
  eventId,
  onViewAnalytics,
  onManageInventory,
  onEditEvent,
}: {
  eventId: string | number;
  onViewAnalytics?: () => void;
  onManageInventory?: () => void;
  onEditEvent?: () => void;
}) => {
  const { data: event, isLoading, isError } = useEventDetail(eventId, true);

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg border border-slate-200 p-4 sm:p-5" aria-busy="true">
        <div className="animate-pulse">
          <div className="h-5 w-40 bg-slate-200 rounded mb-4" />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-4">
            <SkeletonInfoCard />
            <SkeletonTierCard />
            <SkeletonPricingCard />
          </div>
        </div>
      </div>
    );
  }

  if (isError || !event) {
    return <EmptyEventState />;
  }

  const tierData: TierRow[] = event.tiers ?? [];
  const pricingTimeline: PricingPoint[] = event.pricing_timeline ?? [];

  return (
    <div className="bg-white rounded-lg border border-slate-200 p-4 sm:p-5">
      <h3 className="text-base font-bold text-slate-800 mb-3">Event Details</h3>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100">
          <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Event Information</h4>
          <dl className="space-y-2 text-sm text-slate-700">
            <div className="flex justify-between gap-3">
              <dt className="text-slate-500">Event ID</dt>
              <dd className="font-medium text-right truncate">{event.id}</dd>
            </div>
            <div className="flex justify-between gap-3">
              <dt className="text-slate-500">Status</dt>
              <dd className="font-medium text-right">{event.status || 'Active'}</dd>
            </div>
            <div className="flex justify-between gap-3">
              <dt className="text-slate-500">Venue</dt>
              <dd className="font-medium text-right truncate" title={event.venueName || event.venue_name || ''}>
                {event.venueName || event.venue_name || '—'}
              </dd>
            </div>
            <div className="flex justify-between gap-3">
              <dt className="text-slate-500">Date</dt>
              <dd className="font-medium text-right">{event.startDate || event.start_date || '—'}</dd>
            </div>
          </dl>
        </div>

        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100">
          <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tier Breakdown</h4>
          {tierData.length === 0 ? (
            <EmptyBlock>No ticket tiers have been created for this event yet.</EmptyBlock>
          ) : (
            <ul className="space-y-3 text-sm text-slate-700">
              {tierData.map((tier, i) => {
                const pct = tier.capacity > 0 ? Math.round((tier.sold / tier.capacity) * 100) : 0;
                return (
                  <li key={`${tier.tier}-${i}`} className="flex items-center justify-between gap-2">
                    <span className="truncate" title={tier.tier}>{tier.tier}</span>
                    <span className="flex items-center gap-2 flex-shrink-0">
                      <span className="text-xs text-slate-500">{tier.sold}/{tier.capacity}</span>
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-700">
                        {pct}%
                      </span>
                    </span>
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 sm:col-span-2 lg:col-span-1">
          <h4 className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Pricing Timeline</h4>
          {pricingTimeline.length === 0 ? (
            <EmptyBlock>No scheduled price changes for this event.</EmptyBlock>
          ) : (
            <ul className="space-y-2 text-xs text-slate-600">
              {pricingTimeline.map((point, i) => (
                <li key={`${point.label}-${i}`} className="flex items-center justify-between gap-3">
                  <span className="truncate">
                    {point.label}
                    {point.date ? <span className="text-slate-400"> · {point.date}</span> : null}
                  </span>
                  <span className="font-semibold text-slate-800 flex-shrink-0">
                    ₦{Number(point.price || 0).toLocaleString()}
                  </span>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      <div className="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
        <button
          onClick={() => onViewAnalytics?.()}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
          aria-label="View full analytics"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          View Analytics
        </button>
        <button
          onClick={() => onManageInventory?.()}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors"
          aria-label="Manage inventory"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          Manage Inventory
        </button>
        <button
          onClick={() => onEditEvent?.()}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
          aria-label="Edit event"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Edit Event
        </button>
      </div>
    </div>
  );
};

const EventDetailWidget = ({
  eventId,
  onViewAnalytics,
  onManageInventory,
  onEditEvent,
}: {
  eventId?: string | number;
  onViewAnalytics?: () => void;
  onManageInventory?: () => void;
  onEditEvent?: () => void;
}) => {
  const { eventId: routeEventId } = useParams();
  const resolvedEventId = eventId ?? routeEventId;

  if (!resolvedEventId) {
    return (
      <p className="text-sm text-slate-500">
        No event selected — expand an event card from the dashboard to view details.
      </p>
    );
  }

  return (
    <ErrorBoundary
      fallbackRender={(error, reset) => (
        <div className="bg-white rounded-lg border border-rose-200 p-4" role="alert">
          <div className="flex items-start gap-3">
            <svg className="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            <div>
              <h3 className="text-base font-semibold text-rose-800">Failed to load event details</h3>
              <p className="text-sm text-rose-600 mt-1">{error.message}</p>
              <button
                onClick={reset}
                className="mt-3 px-3 py-1.5 text-sm font-medium text-rose-700 bg-rose-100 border border-rose-200 rounded-lg hover:bg-rose-200"
              >
                Try Again
              </button>
            </div>
          </div>
        </div>
      )}
    >
      <EventDetailWidgetContent
        eventId={resolvedEventId}
        onViewAnalytics={onViewAnalytics}
        onManageInventory={onManageInventory}
        onEditEvent={onEditEvent}
      />
    </ErrorBoundary>
  );
};

export default EventDetailWidget;
