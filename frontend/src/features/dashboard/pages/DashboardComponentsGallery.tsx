import React, { useCallback, useMemo, useState } from 'react';
import BaseMetricCard from '@/features/analytics/components/MetricCard';
import { DashboardEventCard } from '../components/DashboardEventCard';
import EventDetailWidget from '../components/EventDetailWidget';
import { SalesActivityFeed } from '../components/SalesActivityFeed';
import { DashboardHeader } from '../components/dashboard/DashboardHeader';
import { MetricCardRow } from '../components/MetricCard';

/* --------------------------------------------------------------------------
 * Dev-only gallery used by tests/dashboard-components.spec.ts.
 *
 * Every component is rendered in isolation with deterministic sample data so
 * the verification suite can assert on real DOM instead of on mocks.
 * --------------------------------------------------------------------------
 */

type LogEntry = { name: string; payload: unknown };

declare global {
  interface Window {
    __dashboardGalleryLog?: LogEntry[];
  }
}

function log(name: string, payload: unknown) {
  if (typeof window === 'undefined') return;
  window.__dashboardGalleryLog = window.__dashboardGalleryLog ?? [];
  window.__dashboardGalleryLog.push({ name, payload });
  window.dispatchEvent(new CustomEvent('dashboard-gallery:log'));
}

function svgImage(label: string, from: string, to: string): string {
  const markup = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${from}"/><stop offset="1" stop-color="${to}"/></linearGradient></defs><rect width="600" height="400" fill="url(#g)"/><circle cx="480" cy="90" r="120" fill="rgba(255,255,255,0.12)"/><text x="50%" y="54%" font-family="Arial, sans-serif" font-size="34" font-weight="bold" fill="#ffffff" text-anchor="middle">${label}</text></svg>`;
  return `data:image/svg+xml;utf8,${encodeURIComponent(markup)}`;
}

const iso = (msOffset: number) => new Date(Date.now() + msOffset).toISOString();

const SAMPLE_EVENT = {
  id: 501,
  title: 'Lagos Jazz Festival 2026',
  desc: 'Three nights of world-class jazz on the Lagos waterfront.',
  bannerUrl: svgImage('JAZZ', '#4f46e5', '#0f172a'),
  status: 'active',
  startDate: iso(9 * 86400000),
  endDate: iso(11 * 86400000),
  venueName: 'Eko Convention Centre',
  revenue: 4_250_000,
  attendeeCount: 812,
  ticketsSold: 812,
  ticketsTotal: 1000,
  organizerName: 'Naija Live Sound',
};

const LONG_TITLE_EVENT = {
  ...SAMPLE_EVENT,
  id: 502,
  title:
    'Annual West African Technology Innovation Summit & Entrepreneurship Networking Retreat 2026',
  bannerUrl: undefined,
  status: 'draft',
  revenue: 0,
  attendeeCount: 0,
  ticketsSold: 0,
  ticketsTotal: 0,
  startDate: undefined,
  venueName: undefined,
};

const Section = ({
  id,
  title,
  description,
  children,
}: {
  id: string;
  title: string;
  description?: string;
  children: React.ReactNode;
}) => (
  <section className="mb-12" data-testid={`section-${id}`}>
    <h2 className="text-xl font-bold text-slate-900 mb-1">{title}</h2>
    {description && <p className="text-sm text-slate-500 mb-4">{description}</p>}
    {children}
  </section>
);

const DashboardComponentsGallery = () => {
  const [expandedEventId, setExpandedEventId] = useState<string | number | null>(null);

  const handleToggle = useCallback((eventId: string | number) => {
    log('eventCard:toggle', { eventId });
    setExpandedEventId((prev) => (prev === eventId ? null : eventId));
  }, []);

  const handleViewAnalytics = useCallback((eventId: string | number) => {
    log('eventCard:viewAnalytics', { eventId, path: `/organizer/events/${eventId}/analytics` });
  }, []);

  const handleManageInventory = useCallback((eventId: string | number) => {
    log('eventCard:manageInventory', { eventId, path: `/organizer/events/${eventId}/inventory` });
  }, []);

  const handleEditEvent = useCallback((eventId: string | number) => {
    log('eventCard:editEvent', { eventId, path: `/organizer/events/${eventId}/edit` });
  }, []);

  const handleFilterChange = useCallback((filter: string) => {
    log('header:filterChange', { filter });
  }, []);

  const handleDateRangeChange = useCallback((range: { from: Date; to: Date }) => {
    log('header:dateRangeChange', {
      from: range.from.toISOString(),
      to: range.to.toISOString(),
    });
  }, []);

  const handleCreateEvent = useCallback(() => {
    log('header:createEvent', { path: '/organizer/events/new' });
  }, []);

  const sampleMetrics = useMemo(
    () => [
      { label: 'Total Revenue', value: '₦4,250,000', trendDirection: 'up' as const, trendPercentage: '+12.5%' },
      { label: 'Active Events', value: '24', trendDirection: 'neutral' as const, trendPercentage: '0%' },
      { label: 'Total Attendees', value: '1,204', trendDirection: 'up' as const, trendPercentage: '+8%' },
      { label: 'Tickets Sold', value: '1,204', trendDirection: 'up' as const, trendPercentage: '+12.5%' },
    ],
    [],
  );

  return (
    <div
      className="min-h-screen bg-slate-50 p-4 md:p-8"
      data-testid="dashboard-gallery"
    >
      <div className="mx-auto max-w-7xl">
        <h1 className="text-2xl font-extrabold text-slate-900 mb-2">
          Dashboard Components Gallery
        </h1>
        <p className="text-sm text-slate-500 mb-8">
          Dev-only harness rendering the five dashboard components with sample data.
        </p>

        <Section id="header" title="DashboardHeader">
          <div data-testid="header-host">
            <DashboardHeader
              user={{ name: 'Amaka Okonkwo' }}
              onFilterChange={handleFilterChange}
              onDateRangeChange={handleDateRangeChange}
              onCreateEvent={handleCreateEvent}
            />
          </div>
        </Section>

        <Section id="metric" title="MetricCard">
          <div data-testid="metric-row">
            <MetricCardRow />
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            {sampleMetrics.map((metric) => (
              <div key={metric.label} data-testid={`metric-${metric.label.replace(/\s+/g, '-').toLowerCase()}`}>
                <BaseMetricCard
                  value={metric.value}
                  label={metric.label}
                  trendDirection={metric.trendDirection}
                  trendPercentage={metric.trendPercentage}
                />
              </div>
            ))}
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <div data-testid="metric-loading">
              <BaseMetricCard value="" label="Total Revenue" loading />
            </div>
            <div data-testid="metric-empty">
              <BaseMetricCard value="" label="Total Revenue" />
            </div>
          </div>
        </Section>

        <Section
          id="event-card"
          title="EventCard"
          description="Tap the card body to expand the EventDetailWidget."
        >
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div data-testid="eventcard-main">
              <DashboardEventCard
                event={SAMPLE_EVENT}
                isExpanded={expandedEventId === SAMPLE_EVENT.id}
                onToggle={() => handleToggle(SAMPLE_EVENT.id)}
                onViewAnalytics={() => handleViewAnalytics(SAMPLE_EVENT.id)}
                onManageInventory={() => handleManageInventory(SAMPLE_EVENT.id)}
                onEditEvent={() => handleEditEvent(SAMPLE_EVENT.id)}
              />
            </div>
            <div data-testid="eventcard-edge">
              <DashboardEventCard
                event={LONG_TITLE_EVENT}
                isExpanded={expandedEventId === LONG_TITLE_EVENT.id}
                onToggle={() => handleToggle(LONG_TITLE_EVENT.id)}
                onViewAnalytics={() => handleViewAnalytics(LONG_TITLE_EVENT.id)}
                onManageInventory={() => handleManageInventory(LONG_TITLE_EVENT.id)}
                onEditEvent={() => handleEditEvent(LONG_TITLE_EVENT.id)}
              />
            </div>
          </div>
        </Section>

        <Section
          id="event-detail"
          title="EventDetailWidget"
          description="Rendered standalone (not only inside an expanded card)."
        >
          <div data-testid="eventdetail-standalone">
            <EventDetailWidget
              eventId={SAMPLE_EVENT.id}
              onViewAnalytics={() => handleViewAnalytics(SAMPLE_EVENT.id)}
              onManageInventory={() => handleManageInventory(SAMPLE_EVENT.id)}
              onEditEvent={() => handleEditEvent(SAMPLE_EVENT.id)}
            />
          </div>
        </Section>

        <Section
          id="activity"
          title="SalesActivityFeed"
          description="Polls every 30s; the stubbed feed returns a new sale on the second fetch."
        >
          <div data-testid="activity-host">
            <SalesActivityFeed />
          </div>
        </Section>
      </div>
    </div>
  );
};

export default DashboardComponentsGallery;
