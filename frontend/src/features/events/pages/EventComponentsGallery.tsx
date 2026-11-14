import React, { useState, useCallback, useEffect } from 'react';
import EventCard from './EventCard';
import FilterSidebar from './FilterSidebar';
import SearchBar from './SearchBar';
import TicketTierCard from './TicketTierCard';
import EventGrid from './EventGrid';
import PricingWindow from './PricingWindow';
import OrganizerCard from './OrganizerCard';
import AvailabilityBadge from './AvailabilityBadge';

type LogEntry = { timestamp: string; component: string; action: string; data?: unknown };

function log(component: string, action: string, data?: unknown) {
  const entry = { timestamp: new Date().toISOString(), component, action, data };
  if (typeof window !== 'undefined') {
    window.__componentGalleryLog = window.__componentGalleryLog ?? [];
    window.__componentGalleryLog.push(entry);
    window.dispatchEvent(new CustomEvent('component-gallery:log'));
  }
  console.log(`[${component}] ${action}`, data ?? '');
}

const mockEvent = {
  id: 1,
  title: 'Jazz Night Live at The Blue Note',
  slug: 'jazz-night-live-blue-note',
  start_datetime: '2026-12-15T19:00:00Z',
  start_date: '2026-12-15T19:00:00Z',
  category: 'Music',
  venue_name: 'The Blue Note, New York',
  venue: 'The Blue Note, New York',
  banner_image_url: 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop',
  image_url: 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop',
  organizer: { name: 'Blue Note Entertainment', id: 1 },
  ticket_price: 15000,
  currency: 'NGN',
  tickets_remaining: 45,
  capacity: 200,
  total_capacity: 200,
  ticket_tiers: [
    { id: 1, name: 'VIP', price: 25000, currency: 'NGN', quantity: 50, sold_count: 10, available_count: 40, is_visible: true, is_sold_out: false },
    { id: 2, name: 'General Admission', price: 15000, currency: 'NGN', quantity: 150, sold_count: 105, available_count: 45, is_visible: true, is_sold_out: false },
  ],
  rating: 4.5,
  ratingCount: 128,
};

const mockEvents = [
  mockEvent,
  {
    ...mockEvent,
    id: 2,
    title: 'Rock Fest 2026',
    slug: 'rock-fest-2026',
    start_datetime: '2026-11-20T20:00:00Z',
    category: 'Festival',
    venue_name: 'Mega Arena, Lagos',
    venue: 'Mega Arena, Lagos',
    ticket_price: 50000,
    tickets_remaining: 0,
    capacity: 5000,
    total_capacity: 5000,
    ticket_tiers: [
      { id: 3, name: 'Early Bird', price: 30000, currency: 'NGN', quantity: 1000, sold_count: 1000, available_count: 0, is_visible: true, is_sold_out: true },
      { id: 4, name: 'Regular', price: 50000, currency: 'NGN', quantity: 4000, sold_count: 4000, available_count: 0, is_visible: true, is_sold_out: true },
    ],
    rating: 4.8,
    ratingCount: 542,
  },
  {
    ...mockEvent,
    id: 3,
    title: 'Tech Conference 2026',
    slug: 'tech-conference-2026',
    start_datetime: '2026-10-05T09:00:00Z',
    category: 'Technology',
    venue_name: 'Convention Center, San Francisco',
    venue: 'Convention Center, San Francisco',
    ticket_price: 75000,
    tickets_remaining: 200,
    capacity: 1000,
    total_capacity: 1000,
    ticket_tiers: [
      { id: 5, name: 'Standard', price: 75000, currency: 'NGN', quantity: 800, sold_count: 600, available_count: 200, is_visible: true, is_sold_out: false },
      { id: 6, name: 'Student', price: 25000, currency: 'NGN', quantity: 200, sold_count: 150, available_count: 50, is_visible: true, is_sold_out: false },
    ],
    rating: 4.2,
    ratingCount: 89,
  },
  {
    ...mockEvent,
    id: 4,
    title: 'Comedy Night',
    slug: 'comedy-night',
    start_datetime: '2026-12-01T20:00:00Z',
    category: 'Comedy',
    venue_name: 'Laugh Factory, LA',
    venue: 'Laugh Factory, LA',
    ticket_price: 8000,
    tickets_remaining: 12,
    capacity: 100,
    total_capacity: 100,
    ticket_tiers: [
      { id: 7, name: 'General', price: 8000, currency: 'NGN', quantity: 100, sold_count: 88, available_count: 12, is_visible: true, is_sold_out: false },
    ],
    rating: 4.6,
    ratingCount: 203,
  },
];

const mockCategories = [
  { id: 1, name: 'Music', slug: 'music' },
  { id: 2, name: 'Festival', slug: 'festival' },
  { id: 3, name: 'Technology', slug: 'technology' },
  { id: 4, name: 'Comedy', slug: 'comedy' },
  { id: 5, name: 'Sports', slug: 'sports' },
  { id: 6, name: 'Arts & Theater', slug: 'arts-theater' },
  { id: 7, name: 'Food & Drink', slug: 'food-drink' },
  { id: 8, name: 'Business', slug: 'business' },
];

const mockPriceRange = { min: 0, max: 200000 };

const mockOrganizer = {
  id: 1,
  name: 'Blue Note Entertainment',
  displayName: 'Blue Note Entertainment',
  avatarUrl: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
  avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
  bio: 'Premier jazz venue and event organizer in New York City. Bringing world-class jazz and blues performances since 1981.',
  publicUrl: '/o/blue-note-entertainment',
};

const mockPricing = {
  currentPrice: 15000,
  nextPrice: 20000,
  nextPriceChangeAt: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString(), // 3 days from now
};

const mockSearchSuggestions = [
  { id: 1, title: 'Jazz Night Live', start_datetime: '2026-12-15T19:00:00Z' },
  { id: 2, title: 'Rock Fest 2026', start_datetime: '2026-11-20T20:00:00Z' },
  { id: 3, title: 'Tech Conference 2026', start_datetime: '2026-10-05T09:00:00Z' },
];

export default function ComponentsGallery() {
  const [filters, setFilters] = useState({
    category: '',
    dateRange: 'upcoming',
    minPrice: 0,
    maxPrice: 200000,
    location: '',
  });
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedTier, setSelectedTier] = useState<string | null>(null);
  const [eventGridLoading, setEventGridLoading] = useState(false);
  const [eventGridHasMore, setEventGridHasMore] = useState(true);
  const [showEventGrid, setShowEventGrid] = useState(true);

  const handleFilterChange = useCallback((newFilters: Partial<typeof filters>) => {
    setFilters((prev) => ({ ...prev, ...newFilters }));
    log('FilterSidebar', 'onChange', newFilters);
  }, []);

  const handleSearch = useCallback((suggestion: { id: number; title: string }) => {
    log('SearchBar', 'onSearch', suggestion);
    setSearchQuery(suggestion.title);
  }, []);

  const handleEventClick = useCallback((event: { id: number; title: string }) => {
    log('EventCard/EventGrid', 'onClick', { id: event.id, title: event.title });
    alert(`Clicked: ${event.title}`);
  }, []);

  const handleLoadMore = useCallback(() => {
    log('EventGrid', 'onLoadMore');
    setEventGridLoading(true);
    setTimeout(() => {
      setEventGridLoading(false);
      setEventGridHasMore(false);
    }, 1000);
  }, []);

  const handleTierSelect = useCallback((tierName: string) => {
    log('TicketTierCard', 'onSelect', tierName);
    setSelectedTier(tierName);
  }, []);

  const handleSearchSuggestion = useCallback((suggestion: { id: number; title: string }) => {
    log('SearchBar', 'suggestion clicked', suggestion);
    setSearchQuery(suggestion.title);
  }, []);

  return (
    <div className="max-w-6xl mx-auto p-6 space-y-12 font-sans">
      <header className="border-b border-slate-200 pb-6">
        <h1 className="text-3xl font-bold text-slate-900">Event Components Gallery</h1>
        <p className="text-slate-600 mt-2">
          Interactive gallery for all Eventiq UI components. Every interaction is logged below.
        </p>
      </header>

      {/* EventCard */}
      <section aria-labelledby="sec-event-card" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-event-card" className="text-lg font-bold text-slate-900 mb-4">EventCard</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <EventCard
            event={mockEvent}
            onClick={handleEventClick}
            onFavoriteToggle={(id) => log('EventCard', 'onFavoriteToggle', id)}
            isFavorite={false}
          />
          <EventCard
            event={{ ...mockEvent, id: 2, title: 'Rock Fest 2026', tickets_remaining: 0, capacity: 5000, currency: 'NGN', ticket_price: 50000 }}
            onClick={handleEventClick}
            onFavoriteToggle={(id) => log('EventCard', 'onFavoriteToggle', id)}
            isFavorite={true}
          />
          <EventCard
            event={{ ...mockEvent, id: 3, title: 'Tech Conference 2026', tickets_remaining: 200, capacity: 1000, currency: 'NGN', ticket_price: 75000 }}
            onClick={handleEventClick}
            onFavoriteToggle={(id) => log('EventCard', 'onFavoriteToggle', id)}
          />
          <EventCard event={mockEvent} loading={true} />
          <EventCard event={{ ...mockEvent, id: 4, title: 'Event with very long title that should truncate properly', tickets_remaining: 12, capacity: 100, currency: 'NGN', ticket_price: 8000 }} onClick={handleEventClick} />
          <EventCard event={{ ...mockEvent, id: 5, title: 'Event without organizer', organizer: undefined, tickets_remaining: 50, capacity: 200, currency: 'NGN', ticket_price: 10000 }} onClick={handleEventClick} />
        </div>
      </section>

      {/* FilterSidebar */}
      <section aria-labelledby="sec-filter-sidebar" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-filter-sidebar" className="text-lg font-bold text-slate-900 mb-4">FilterSidebar</h2>
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <div className="lg:col-span-1">
            <FilterSidebar
              currentFilters={filters}
              onChange={handleFilterChange}
              availableCategories={mockCategories}
              priceRange={mockPriceRange}
            />
          </div>
          <div className="lg:col-span-3 space-y-4">
            <div className="bg-slate-50 p-4 rounded-lg">
              <h4 className="font-semibold mb-2">Current Filters:</h4>
              <pre className="text-xs text-slate-700 bg-white p-3 rounded">{JSON.stringify(filters, null, 2)}</pre>
            </div>
            <div className="bg-slate-50 p-4 rounded-lg">
              <h4 className="font-semibold mb-2">
                Mobile Preview (resize window {'< 768px'}):
              </h4>
              <p className="text-sm text-slate-600">FilterSidebar becomes a collapsible drawer with ☰ toggle button</p>
            </div>
          </div>
        </div>
      </section>

      {/* SearchBar */}
      <section aria-labelledby="sec-search-bar" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-search-bar" className="text-lg font-bold text-slate-900 mb-4">SearchBar</h2>
        <div className="max-w-md">
          <SearchBar onSearch={handleSearchSuggestion} placeholder="Search events, venues, artists…" />
          <p className="mt-2 text-sm text-slate-500">
            Type at least 2 characters to see autocomplete suggestions. 
            Try: "Jazz", "Rock", "Tech", "Comedy"
          </p>
          <div className="mt-4 p-4 bg-slate-50 rounded-lg">
            <h4 className="font-semibold mb-2">Last Selected:</h4>
            <p>{searchQuery || 'None yet'}</p>
          </div>
        </div>
      </section>

      {/* TicketTierCard */}
      <section aria-labelledby="sec-ticket-tier-card" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-ticket-tier-card" className="text-lg font-bold text-slate-900 mb-4">TicketTierCard</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {mockEvent.ticket_tiers.map((tier) => (
            <TicketTierCard
              key={tier.id}
              tier={tier}
              onSelect={() => handleTierSelect(tier.name)}
              loading={false}
            />
          ))}
          <TicketTierCard
            tier={{ ...mockEvent.ticket_tiers[0], available_count: 0, is_sold_out: true }}
            onSelect={() => handleTierSelect('VIP (Sold Out)')}
          />
          <TicketTierCard
            tier={{ ...mockEvent.ticket_tiers[1], available_count: 5, quantity: 100 }}
            onSelect={() => handleTierSelect('Low Stock')}
          />
          <TicketTierCard tier={null} onSelect={() => {}} />
        </div>
        <div className="mt-4 p-4 bg-slate-50 rounded-lg">
          <h4 className="font-semibold mb-2">Last Selected Tier:</h4>
          <p>{selectedTier || 'None yet'}</p>
        </div>
      </section>

      {/* AvailabilityBadge */}
      <section aria-labelledby="sec-availability-badge" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-availability-badge" className="text-lg font-bold text-slate-900 mb-4">AvailabilityBadge</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="p-4 bg-slate-50 rounded-lg">
            <h4 className="font-semibold mb-2">Plenty (45/200)</h4>
            <AvailabilityBadge ticketsAvailable={45} ticketsTotal={200} />
          </div>
          <div className="p-4 bg-slate-50 rounded-lg">
            <h4 className="font-semibold mb-2">Low (12/100)</h4>
            <AvailabilityBadge ticketsAvailable={12} ticketsTotal={100} />
          </div>
          <div className="p-4 bg-slate-50 rounded-lg">
            <h4 className="font-semibold mb-2">Sold Out (0/5000)</h4>
            <AvailabilityBadge ticketsAvailable={0} ticketsTotal={5000} />
          </div>
          <div className="p-4 bg-slate-50 rounded-lg">
            <h4 className="font-semibold mb-2">No Total Capacity</h4>
            <AvailabilityBadge ticketsAvailable={100} ticketsTotal={0} />
          </div>
        </div>
      </section>

      {/* PricingWindow */}
      <section aria-labelledby="sec-pricing-window" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-pricing-window" className="text-lg font-bold text-slate-900 mb-4">PricingWindow</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-2xl">
          <PricingWindow pricing={mockPricing} currency="NGN" />
          <PricingWindow pricing={{ ...mockPricing, nextPrice: null, nextPriceChangeAt: null }} currency="NGN" />
          <PricingWindow pricing={{ ...mockPricing, currency: 'USD', currentPrice: 150, nextPrice: 200 }} currency="USD" />
        </div>
      </section>

      {/* OrganizerCard */}
      <section aria-labelledby="sec-organizer-card" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-organizer-card" className="text-lg font-bold text-slate-900 mb-4">OrganizerCard</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <OrganizerCard organizer={mockOrganizer} onClick={() => log('OrganizerCard', 'onClick', mockOrganizer.id)} />
          <OrganizerCard
            organizer={{ ...mockOrganizer, id: 2, displayName: 'Organizer Without Avatar', avatarUrl: null, bio: 'A bio that is very long and should be truncated after 100 characters to show the ellipsis behavior properly.' }}
            onClick={() => log('OrganizerCard', 'onClick', 2)}
          />
          <OrganizerCard
            organizer={{ ...mockOrganizer, id: 3, displayName: 'Short Name', bio: '' }}
            onClick={() => log('OrganizerCard', 'onClick', 3)}
          />
        </div>
      </section>

      {/* EventGrid */}
      <section aria-labelledby="sec-event-grid" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-event-grid" className="text-lg font-bold text-slate-900 mb-4">EventGrid</h2>
        <div className="space-y-4">
          <div className="flex gap-2">
            <button onClick={() => { setShowEventGrid(true); setEventGridHasMore(true); }} className="px-3 py-1 rounded bg-indigo-600 text-white text-sm">Show Grid (4 events)</button>
            <button onClick={() => { setShowEventGrid(true); setEventGridHasMore(true); setTimeout(() => setEventGridLoading(true), 0); }} className="px-3 py-1 rounded bg-amber-600 text-white text-sm">Show Loading</button>
            <button onClick={() => { setShowEventGrid(true); setEventGridHasMore(false); }} className="px-3 py-1 rounded bg-red-600 text-white text-sm">Show Empty (no events)</button>
            <button onClick={() => setShowEventGrid(false)} className="px-3 py-1 rounded bg-slate-600 text-white text-sm">Hide Grid</button>
          </div>
          {showEventGrid && (
            <EventGrid
              events={mockEvents}
              isLoading={eventGridLoading}
              hasMore={eventGridHasMore}
              onLoadMore={handleLoadMore}
              onEventClick={handleEventClick}
            />
          )}
        </div>
      </section>

      {/* Callback log */}
      <section aria-labelledby="sec-log" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-log" className="text-lg font-bold text-slate-900 mb-4">
          Callback Log
          <button
            onClick={() => {
              if (typeof window !== 'undefined') {
                window.__componentGalleryLog = [];
                window.dispatchEvent(new CustomEvent('component-gallery:log'));
              }
            }}
            className="ml-3 text-xs px-2 py-1 rounded bg-slate-200 text-slate-700 hover:bg-slate-300"
          >
            Clear
          </button>
        </h2>
        <pre
          data-testid="component-cb-log"
          className="bg-slate-900 text-slate-300 text-xs rounded p-4 overflow-auto max-h-64 font-mono"
        >
          {JSON.stringify((typeof window !== 'undefined' ? (window as any).__componentGalleryLog : []) ?? [], null, 2)}
        </pre>
      </section>
    </div>
  );
}