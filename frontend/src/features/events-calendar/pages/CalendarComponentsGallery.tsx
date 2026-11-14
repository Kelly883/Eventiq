import React, { useCallback, useMemo, useState } from 'react';

import CalendarGrid, { CalendarEvent } from '../components/calendar/CalendarGrid';
import type { Event } from '../types';
import DateHeader from '../components/calendar/DateHeader';
import EventCard from '../components/events/EventCard';
import AvailabilityIndicator from '../components/events/AvailabilityIndicator';
import FilterSidebar from '../components/filters/FilterSidebar';
import SearchBar from '../components/filters/SearchBar';

/**
 * Dev-only gallery that renders every events-calendar component in isolation
 * with mock data. Every callback is logged to window.__galleryLog so Playwright
 * tests can assert that interactions actually fired.
 *
 * Accessible at /calendar-components (DEV only).
 */

/* -------------------------------------------------------------------------- *
 * Callback log
 * -------------------------------------------------------------------------- */

type LogEntry = { name: string; payload: unknown };

declare global {
  interface Window {
    __galleryLog?: LogEntry[];
  }
}

function log(name: string, payload: unknown) {
  if (typeof window === 'undefined') return;
  window.__galleryLog = window.__galleryLog ?? [];
  window.__galleryLog.push({ name, payload });
  window.dispatchEvent(new CustomEvent('gallery:log'));
}

/* -------------------------------------------------------------------------- *
 * Mock data
 * -------------------------------------------------------------------------- */

function svgImage(label: string, from: string, to: string): string {
  const markup = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${from}"/><stop offset="1" stop-color="${to}"/></linearGradient></defs><rect width="600" height="400" fill="url(#g)"/><text x="50%" y="54%" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="#ffffff" text-anchor="middle">${label}</text></svg>`;
  return `data:image/svg+xml;utf8,${encodeURIComponent(markup)}`;
}

// Local noon keeps toISOString() date keys stable regardless of server TZ.
function noon(isoDate: string): Date {
  const [y, m, d] = isoDate.split('-').map(Number);
  return new Date(y, m - 1, d, 12, 0, 0);
}
const addHours = (d: Date, h: number) => new Date(d.getTime() + h * 3_600_000);
const calendarEvents = [
  {
    id: 1,
    title: 'Lagos Jazz Night',
    start: noon('2026-03-14'),
    end: addHours(noon('2026-03-14'), 2),
    ticketTiers: [{ totalTickets: 200, soldCount: 40 }],
  },
  {
    id: 2,
    title: 'Tech Summit 2026',
    start: noon('2026-03-17'),
    end: addHours(noon('2026-03-17'), 2),
    ticketTiers: [{ totalTickets: 100, soldCount: 95 }],
  },
  {
    id: 3,
    title: 'Sold Out Conference',
    start: noon('2026-03-20'),
    end: addHours(noon('2026-03-20'), 2),
    ticketTiers: [{ totalTickets: 100, soldCount: 100 }],
  },
  {
    id: 4,
    title: 'Art Exhibition',
    start: noon('2026-03-28'),
    end: addHours(noon('2026-03-28'), 2),
    ticketTiers: [{ totalTickets: 50, soldCount: 5 }],
  },
  {
    id: 5,
    title: 'Poetry Slam',
    start: noon('2026-03-28'),
    end: addHours(noon('2026-03-28'), 2),
    ticketTiers: [{ totalTickets: 80, soldCount: 10 }],
  },
] as CalendarEvent[];

const baseOrganizer = {
  id: 1,
  name: 'Amaka Okonkwo',
  brandingColors: { primary: '#7c3aed', secondary: '#db2777' },
};

const cardEvents = [
  {
    id: 101,
    title: 'Lagos Jazz Night',
    startTime: '19:00:00',
    endTime: '23:00:00',
    venue: { name: 'Eko Convention Centre' },
    bannerUrl: svgImage('LJN', '#7c3aed', '#db2777'),
    ticketTiers: [{ currentPrice: 5000, ticketsAvailable: 160, totalTickets: 200 }],
    category: 'Music',
    organizer: baseOrganizer,
  },
  {
    id: 102,
    title: 'Tech Meetup',
    startTime: '14:00:00',
    endTime: '18:00:00',
    venue: { name: 'CoWork Hub' },
    bannerUrl: svgImage('TM', '#2563eb', '#1d4ed8'),
    ticketTiers: [{ currentPrice: 2500, ticketsAvailable: 8, totalTickets: 200 }],
    category: 'Tech',
    organizer: baseOrganizer,
  },
  {
    id: 103,
    title: 'Out of Tickets Gala',
    startTime: '20:00:00',
    endTime: '23:00:00',
    venue: { name: 'Grand Ballroom' },
    bannerUrl: svgImage('OT', '#dc2626', '#b91c1c'),
    ticketTiers: [{ currentPrice: 15000, ticketsAvailable: 0, totalTickets: 120 }],
    category: 'Gala',
    organizer: baseOrganizer,
  },
] as unknown as Event[];

const searchResults = [
  { id: 1, title: 'Lagos Jazz Night', organizerName: 'Amaka Okonkwo', category: 'Music' },
  { id: 2, title: 'Jazz Brunch Rooftop', organizerName: 'The Jazz Collective', category: 'Food' },
  { id: 3, title: 'Tech Summit 2026', organizerName: 'DevCon Africa', category: 'Tech' },
];

export const CalendarComponentsGallery = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResultsVisible, setSearchResultsVisible] = useState<any[]>([]);

  const handleSearch = useCallback(
    (query: string) => {
      log('search:query', { query });
      const q = query.trim().toLowerCase();
      if (q.length < 2) {
        setSearchResultsVisible([]);
        return;
      }
      setSearchResultsVisible(
        searchResults.filter((r) => r.title.toLowerCase().includes(q))
      );
    },
    []
  );

  const handleSelectResult = useCallback(
    (result: any) => {
      log('search:select', { id: result.id, title: result.title });
      setSearchQuery(result.title);
      setSearchResultsVisible([]);
    },
    []
  );

  return (
    <div data-testid="gallery-root" className="space-y-8 p-4 md:p-6 max-w-5xl mx-auto">
      <h1 className="text-2xl font-bold">Calendar Components Gallery</h1>
      <p className="text-sm text-gray-500">
        Mock data. Every interaction is logged below.
      </p>

      {/* ---------- CalendarGrid ---------- */}
      <section
        id="sec-calendar"
        data-testid="section-calendar-grid"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">CalendarGrid</h2>
        <div style={{ maxWidth: 900 }}>
          <CalendarGrid
            events={calendarEvents}
            onDateSelect={(date: string) => log('calendar:dateSelect', { date })}
            onViewChange={(view: 'month' | 'week' | 'day') =>
              log('calendar:viewChange', { view })
            }
            view="month"
          />
        </div>
        <p className="text-xs text-gray-500 mt-2">
          Day cells carry role=gridcell, an aria-label and keyboard
          (Enter/Space) selection. Left border color shows availability
          (green/amber/red).
        </p>
      </section>

      {/* ---------- DateHeader ---------- */}
      <section
        id="sec-dateheaders"
        data-testid="section-date-header"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">DateHeader</h2>
        <div className="flex flex-wrap gap-4">
          {calendarEvents.slice(0, 3).map((ev) => (
            <div key={ev.id} data-testid={`dateheader-${ev.id}`}>
              <DateHeader
                date={ev.start.toISOString().split('T')[0]}
                eventCount={1}
              />
            </div>
          ))}
          <div data-testid="dateheader-multi">
            <DateHeader date="2026-03-28" eventCount={2} />
          </div>
        </div>
      </section>

      {/* ---------- EventCard ---------- */}
      <section
        id="sec-eventcards"
        data-testid="section-event-card"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">EventCard</h2>
        <div className="flex flex-wrap gap-4">
          {cardEvents.map((ev) => (
            <div key={ev.id} data-testid={`eventcard-${ev.id}`} style={{ width: 280 }}>
              <EventCard
                event={ev}
                onClick={() => log('eventcard:click', { id: ev.id })}
              />
            </div>
          ))}
        </div>
        <p className="text-xs text-gray-500 mt-2">
          Fields: thumbnail, title, organizer, date, price, availability.
          Focusable (Tab) and activatable with Enter/Space.
        </p>
      </section>

      {/* ---------- AvailabilityIndicator ---------- */}
      <section
        id="sec-availability"
        data-testid="section-availability"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">AvailabilityIndicator</h2>
        <div className="flex flex-wrap gap-6 items-end">
          <div data-testid="indicator-available">
            <AvailabilityIndicator availability="available" ticketsRemaining={45} />
          </div>
          <div data-testid="indicator-low">
            <AvailabilityIndicator availability="low" ticketsRemaining={12} />
          </div>
          <div data-testid="indicator-soldout">
            <AvailabilityIndicator availability="sold_out" />
          </div>
          <div data-testid="indicator-undefined">
            <AvailabilityIndicator availability="available" />
          </div>
        </div>
      </section>

      {/* ---------- FilterSidebar ---------- */}
      <section
        id="sec-filters"
        data-testid="section-filter-sidebar"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">FilterSidebar</h2>
        <div style={{ maxWidth: 360 }}>
          <FilterSidebar
            categories={['Music', 'Tech', 'Food', 'Sports']}
            selectedCategory={null}
            onCategoryChange={(category) =>
              log('filter:category', { category })
            }
            priceRange={{ min: 0, max: 200 }}
            onPriceChange={(range) => log('filter:price', range)}
            location=""
            onLocationChange={(location) =>
              log('filter:location', { location })
            }
            onClearFilters={() => log('filter:clear', {})}
          />
        </div>
      </section>

      {/* ---------- SearchBar ---------- */}
      <section
        id="sec-search"
        data-testid="section-search-bar"
        className="border rounded-xl p-4 md:p-6"
      >
        <h2 className="text-lg font-semibold mb-3">SearchBar</h2>
        <div style={{ maxWidth: 420 }}>
          <SearchBar
            placeholder="Search events..."
            onSearch={handleSearch}
            onSelectResult={handleSelectResult}
          />
          {searchResultsVisible.length > 0 && (
            <p className="text-xs text-gray-500 mt-2">
              Results: {searchResultsVisible.map((r) => r.title).join(', ')}
            </p>
          )}
          <p className="text-xs text-gray-500 mt-2">
            Debounced (300 ms), arrow-key navigable, Enter/Escape supported.
          </p>
        </div>
      </section>

      {/* ---------- Callback log ---------- */}
      <section className="border rounded-xl p-4 md:p-6">
        <h2 className="text-lg font-semibold mb-3">Callback log</h2>
        <pre
          data-testid="cb-log"
          className="bg-gray-900 text-gray-300 rounded p-3 text-xs overflow-auto max-h-64"
        >
          {JSON.stringify(window.__galleryLog ?? [], null, 2)}
        </pre>
      </section>
    </div>
  );
};

export default CalendarComponentsGallery;
