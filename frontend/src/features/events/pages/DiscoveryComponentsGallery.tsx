import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import EventCard from '../components/EventCard.tsx';
import EventGrid from '../components/EventGrid.tsx';
import FilterSidebar from '../components/FilterSidebar.tsx';
import SearchBar from '../components/SearchBar.tsx';
import TicketTierCard from '../components/TicketTierCard.tsx';
import PricingWindow from '../components/PricingWindow.tsx';
import OrganizerCard from '../components/OrganizerCard.tsx';
import AvailabilityBadge from '../components/AvailabilityBadge.tsx';
import type {
  DiscoveryCategory,
  Event,
  Organizer,
  SharedOrganizer,
  SharedTicketTier,
} from '../types/shared';

/* --------------------------------------------------------------------------
 * Callback log: every component callback is recorded here so tests (and
 * humans) can verify that interactions actually fired.
 * ------------------------------------------------------------------------ */

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

/* --------------------------------------------------------------------------
 * Mock data
 * ------------------------------------------------------------------------ */

function svgImage(label: string, from: string, to: string): string {
  const markup = `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${from}"/><stop offset="1" stop-color="${to}"/></linearGradient></defs><rect width="600" height="400" fill="url(#g)"/><text x="50%" y="54%" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="#ffffff" text-anchor="middle">${label}</text></svg>`;
  return `data:image/svg+xml;utf8,${encodeURIComponent(markup)}`;
}

const nowIso = () => new Date().toISOString();
const daysFromNow = (days: number) =>
  new Date(Date.now() + days * 86400000).toISOString();

const baseOrganizer: Organizer = {
  id: 1,
  user_id: 1,
  userId: '1',
  displayName: 'Amaka Okonkwo',
  bio: 'Award-winning producer curating live music experiences across West Africa since 2016.',
  avatarUrl: svgImage('AO', '#7c3aed', '#db2777'),
  email: 'amaka@example.com',
  phone: null,
  website: null,
  socialLinks: null,
  brandingColors: null,
  timezone: null,
  currency: null,
  country: null,
  verificationStatus: null,
  paymentDefault: null,
  commissionRate: null,
  isPublic: true,
  emailPublic: false,
  phonePublic: false,
  hideSocialLinks: false,
  hideBrandingColors: false,
  notificationPreferences: null,
  totalEventsCreated: 24,
  totalTicketsSold: 3120,
  createdAt: nowIso(),
  updatedAt: nowIso(),
  deletedAt: null,
};

const baseTier = {
  id: 1,
  event_id: 1,
  name: 'General Admission',
  price: 5000,
  currency: 'NGN',
  quantity: 200,
  sold_count: 40,
  available_count: 160,
  is_visible: true,
  is_sold_out: false,
} as unknown as Event['ticket_tiers'][number];

type MockEventInput = {
  id: number;
  title: string;
  category?: string | null;
  image?: string | null;
  start?: string;
  venue?: string;
  price?: number | null;
  remaining?: number | null;
  capacity?: number;
  organizer?: Organizer | null;
  tiers?: Event['ticket_tiers'];
  rating?: number | null;
  ratingCount?: number | null;
};

function makeEvent(input: MockEventInput): Event {
  return {
    id: input.id,
    user_id: 1,
    organizer_id: 1,
    title: input.title,
    description: 'A fantastic event you will not want to miss.',
    banner_image_url:
      input.image === undefined
        ? svgImage(input.title.slice(0, 18), '#0f766e', '#2563eb')
        : input.image,
    start_datetime: input.start ?? daysFromNow(7),
    end_datetime: daysFromNow(8),
    venue_name: input.venue ?? 'Eko Convention Centre',
    venue_address: '12 Marina Road, Lagos',
    latitude: 6.5244,
    longitude: 3.3792,
    capacity: input.capacity ?? 500,
    status: 'published',
    category: input.category === undefined ? 'Music' : input.category,
    deleted_at: null,
    ticket_tiers: input.tiers ?? [baseTier],
    organizer: (input.organizer === undefined
      ? baseOrganizer
      : input.organizer) as Organizer,
    analyticsMetrics: null,
    created_at: nowIso(),
    updated_at: nowIso(),
    rating: input.rating ?? 4,
    ratingCount: input.ratingCount ?? 128,
    // Extra snake_case fields consumed by EventCard's public-payload support.
    ticket_price: input.price === undefined ? 5000 : input.price,
    tickets_remaining: input.remaining === undefined ? 160 : input.remaining,
  } as unknown as Event;
}

const eventTitles = [
  'Lagos Jazz Night',
  'TechSummit Africa',
  'Marathon Miles 10K',
  'Indie Film Showcase',
  'Afrobeats Beach Party',
  'Startup Pitch Night',
  'Lagos Food Festival',
  'Comedy Raw & Uncut',
  'Art Basel Lagos',
  'Gospel Praise Concert',
  'DevOps Meetup',
  'Yoga in the Park',
];

const mockEvents: Event[] = eventTitles.map((title, i) =>
  makeEvent({
    id: i + 1,
    title,
    category: ['Music', 'Tech', 'Sports', 'Film', 'Nightlife'][i % 5],
    price: [5000, 25000, 0, 12000, 7500][i % 5],
    remaining: [160, 45, 12, 0, 210, 3, 90][i % 7],
    venue: ['Eko Convention Centre', 'Freedom Park', 'Tafawa Balewa Square'][
      i % 3
    ],
    rating: (i % 5) + 1,
    ratingCount: 12 + i * 7,
  }),
);

const longTitleEvent = makeEvent({
  id: 901,
  title:
    'The Extraordinarily Long Annual Pan-African Conference on Sustainable Development and Digital Innovation',
  price: 15000,
});
const noImageEvent = makeEvent({ id: 902, title: 'Unannounced Secret Show', image: null });
const brokenImageEvent = makeEvent({
  id: 903,
  title: 'Broken Image Event',
  image: '/definitely-missing-banner.jpg',
});
const noOrganizerEvent = makeEvent({
  id: 904,
  title: 'Community Cleanup Drive',
  organizer: { id: 904 } as unknown as Organizer,
  price: null,
  remaining: null,
  capacity: 0,
});
const soldOutEvent = makeEvent({
  id: 905,
  title: 'Sold Out Launch Party',
  remaining: 0,
  capacity: 300,
});

const categories: DiscoveryCategory[] = [
  { id: 1, slug: 'music', name: 'Music', events_count: 128 },
  { id: 2, slug: 'tech', name: 'Tech', events_count: 64 },
  { id: 3, slug: 'sports', name: 'Sports', events_count: 41 },
  { id: 4, slug: 'nightlife', name: 'Nightlife', events_count: 77 },
  { id: 5, slug: 'art', name: 'Art & Culture', events_count: 33 },
];

const tiers: {
  key: string;
  tier: SharedTicketTier;
}[] = [
  {
    key: 'normal',
    tier: {
      id: 1,
      name: 'General Admission',
      price: 5000,
      currency: 'NGN',
      quantity: 200,
      sold_count: 40,
      available_count: 160,
    },
  },
  {
    key: 'low',
    tier: {
      id: 2,
      name: 'VIP Front Row',
      price: 45000,
      currency: 'NGN',
      quantity: 100,
      sold_count: 92,
      available_count: 8,
    },
  },
  {
    key: 'soldout',
    tier: {
      id: 3,
      name: 'Early Bird',
      price: 2500,
      currency: 'NGN',
      quantity: 50,
      sold_count: 50,
      available_count: 0,
    },
  },
  {
    key: 'unknown',
    tier: { id: 4, name: 'Table for 6', price: 120000, currency: 'NGN' },
  },
];

const organizers: { key: string; organizer: SharedOrganizer }[] = [
  {
    key: 'full',
    organizer: {
      id: 1,
      displayName: 'Amaka Okonkwo',
      bio: 'Award-winning producer curating live music experiences across West Africa since 2016.',
      avatarUrl: svgImage('AO', '#7c3aed', '#db2777'),
      publicUrl: '/o/1',
    },
  },
  {
    key: 'minimal',
    organizer: { id: 2, name: 'Tunde Adeyemi' },
  },
  {
    key: 'longbio',
    organizer: {
      id: 3,
      displayName: 'The Pan-African Cultural Collective',
      bio: 'We are a community-driven organisation with a very long story to tell about everything we have achieved, including workshops, touring shows, youth mentorship programmes and a partridge in a pear tree.',
      avatar: svgImage('PC', '#b45309', '#dc2626'),
    },
  },
  {
    key: 'brokenavatar',
    organizer: {
      id: 4,
      displayName: 'Ghost Producer',
      avatarUrl: '/missing-avatar.jpg',
      bio: 'Avatar link that 404s.',
    },
  },
];

const badges: { key: string; available: number | null; total: number }[] = [
  { key: 'green', available: 45, total: 100 },
  { key: 'amber', available: 12, total: 100 },
  { key: 'red', available: 3, total: 100 },
  { key: 'soldout', available: 0, total: 100 },
  { key: 'critical', available: 1, total: 100 },
  { key: 'zerototal', available: null, total: 0 },
];

/* --------------------------------------------------------------------------
 * Gallery page
 * ------------------------------------------------------------------------ */

const sectionStyle: React.CSSProperties = {
  borderBottom: '1px solid #e5e7eb',
  padding: '24px 20px',
};

const headingStyle: React.CSSProperties = {
  fontSize: 13,
  fontWeight: 700,
  textTransform: 'uppercase',
  letterSpacing: '0.06em',
  color: '#6b7280',
  margin: '0 0 16px',
};

const rowStyle: React.CSSProperties = {
  display: 'flex',
  flexWrap: 'wrap',
  gap: 16,
  alignItems: 'flex-start',
};

export const DiscoveryComponentsGallery = () => {
  const [searchParams] = useSearchParams();
  const gridVariant = searchParams.get('grid') ?? 'full';
  const pricingVariant = searchParams.get('pricing') ?? 'days';

  const [filters, setFilters] = useState<{
    category?: string;
    dateRange?: string;
    minPrice?: number;
    maxPrice?: number;
    location?: string;
  }>({});

  const filterChange = useCallback((next: typeof filters) => {
    setFilters(next);
    log('filter:change', next);
  }, []);

  const [gridEvents, setGridEvents] = useState<Event[]>(mockEvents);
  const [gridLimit, setGridLimit] = useState(12);
  const [, setLogTick] = useState(0);

  useEffect(() => {
    const handler = () => setLogTick((t) => t + 1);
    window.addEventListener('gallery:log', handler);
    return () => window.removeEventListener('gallery:log', handler);
  }, []);

  const gridProps = useMemo(() => {
    if (gridVariant === 'loading') {
      return { events: [] as Event[], isLoading: true, hasMore: false };
    }
    if (gridVariant === 'empty') {
      return { events: [] as Event[], isLoading: false, hasMore: false };
    }
    if (gridVariant === 'hasmore') {
      return {
        events: gridEvents.slice(0, gridLimit),
        isLoading: false,
        hasMore: gridLimit < gridEvents.length,
      };
    }
    return { events: gridEvents, isLoading: false, hasMore: false };
  }, [gridVariant, gridEvents, gridLimit]);

  const handleLoadMore = useCallback(() => {
    log('grid:loadMore', { shown: Math.min(gridLimit, gridEvents.length) });
    if (gridLimit >= gridEvents.length) {
      setGridEvents((prev) => [
        ...prev,
        ...mockEvents.map((e) => ({ ...e, id: e.id + 1000 })),
      ]);
    }
    setGridLimit((n) => n + 12);
  }, [gridLimit, gridEvents.length]);

  const pricing = useMemo(() => {
    if (pricingVariant === 'tick') {
      return {
        currentPrice: 12500,
        nextPrice: 15000,
        nextPriceChangeAt: new Date(Date.now() + 9000),
      };
    }
    if (pricingVariant === 'past') {
      return {
        currentPrice: 12500,
        nextPrice: 15000,
        nextPriceChangeAt: new Date(Date.now() - 1000),
      };
    }
    if (pricingVariant === 'none') {
      return { currentPrice: 12500 };
    }
    return {
      currentPrice: 12500,
      nextPrice: 17500,
      nextPriceChangeAt: daysFromNow(3),
    };
  }, [pricingVariant]);

  return (
    <div
      data-testid="discovery-gallery"
      style={{ maxWidth: 1200, margin: '0 auto', fontFamily: 'Inter, system-ui, sans-serif' }}
    >
      <section id="sec-eventcard" style={sectionStyle}>
        <h2 style={headingStyle}>EventCard</h2>
        <div style={rowStyle}>
          <div data-testid="eventcard-main" style={{ width: 320 }}>
            <EventCard
              event={mockEvents[0]}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
              onFavoriteToggle={(id) => log('eventCard:favorite', { id })}
            />
          </div>
          <div data-testid="eventcard-loading" style={{ width: 320 }}>
            <EventCard event={mockEvents[1]} loading />
          </div>
          <div data-testid="eventcard-soldout" style={{ width: 320 }}>
            <EventCard
              event={soldOutEvent}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
            />
          </div>
          <div data-testid="eventcard-longtitle" style={{ width: 320 }}>
            <EventCard
              event={longTitleEvent}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
            />
          </div>
          <div data-testid="eventcard-noimage" style={{ width: 320 }}>
            <EventCard
              event={noImageEvent}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
            />
          </div>
          <div data-testid="eventcard-brokenimage" style={{ width: 320 }}>
            <EventCard
              event={brokenImageEvent}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
            />
          </div>
          <div data-testid="eventcard-noorganizer" style={{ width: 320 }}>
            <EventCard
              event={noOrganizerEvent}
              onClick={(e) => log('eventCard:click', { id: e.id, title: e.title })}
            />
          </div>
        </div>
      </section>

      <section id="sec-filter" style={sectionStyle}>
        <h2 style={headingStyle}>FilterSidebar</h2>
        <div style={{ display: 'flex', gap: 24, alignItems: 'flex-start' }}>
          <div data-testid="filter-sidebar-wrap" style={{ width: 300 }}>
            <FilterSidebar
              currentFilters={filters}
              onChange={filterChange}
              availableCategories={categories}
              priceRange={{ min: 0, max: 100000 }}
            />
          </div>
          <pre
            data-testid="filter-state"
            style={{
              background: '#f7f8fa',
              border: '1px solid #e5e7eb',
              borderRadius: 8,
              padding: 12,
              fontSize: 12,
              margin: 0,
            }}
          >
            {JSON.stringify(filters, null, 2)}
          </pre>
        </div>
      </section>

      <section id="sec-search" style={sectionStyle}>
        <h2 style={headingStyle}>SearchBar</h2>
        <div data-testid="search-wrap" style={{ width: 480 }}>
          <SearchBar onSearch={(s) => log('search:select', { id: s.id, title: s.title })} />
        </div>
      </section>

      <section id="sec-tiers" style={sectionStyle}>
        <h2 style={headingStyle}>TicketTierCard</h2>
        <div style={rowStyle}>
          {tiers.map(({ key, tier }) => (
            <div key={key} data-testid={`tier-${key}`} style={{ width: 220 }}>
              <TicketTierCard tier={tier} onSelect={() => log('tier:select', { key })} />
            </div>
          ))}
          <div data-testid="tier-loading" style={{ width: 220 }}>
            <TicketTierCard tier={tiers[0].tier} loading onSelect={() => {}} />
          </div>
        </div>
      </section>

      <section id="sec-grid" style={sectionStyle}>
        <h2 style={headingStyle}>EventGrid ({gridVariant})</h2>
        <div data-testid="event-grid-wrap">
          <EventGrid
            events={gridProps.events}
            isLoading={gridProps.isLoading}
            hasMore={gridProps.hasMore}
            onLoadMore={() => {
              if (gridVariant === 'hasmore') handleLoadMore();
              else log('grid:loadMore', { variant: gridVariant });
            }}
            onEventClick={(e) => log('grid:eventClick', { id: e.id })}
          />
        </div>
      </section>

      <section id="sec-pricing" style={sectionStyle}>
        <h2 style={headingStyle}>PricingWindow ({pricingVariant})</h2>
        <div data-testid="pricing-wrap" style={{ maxWidth: 320 }}>
          <PricingWindow pricing={pricing} currency="NGN" />
        </div>
      </section>

      <section id="sec-organizer" style={sectionStyle}>
        <h2 style={headingStyle}>OrganizerCard</h2>
        <div style={rowStyle}>
          {organizers.map(({ key, organizer }) => (
            <div key={key} data-testid={`organizer-${key}`} style={{ width: 340 }}>
              <OrganizerCard
                organizer={organizer}
                onClick={() => log('organizer:click', { key })}
              />
            </div>
          ))}
        </div>
      </section>

      <section id="sec-badges" style={sectionStyle}>
        <h2 style={headingStyle}>AvailabilityBadge</h2>
        <div style={rowStyle}>
          {badges.map(({ key, available, total }) => (
            <div
              key={key}
              data-testid={`badge-${key}`}
              style={{ display: 'flex', flexDirection: 'column', gap: 6, alignItems: 'flex-start' }}
            >
              <span style={{ fontSize: 11, color: '#6b7280' }}>{key}</span>
              <AvailabilityBadge ticketsAvailable={available} ticketsTotal={total} />
            </div>
          ))}
        </div>
      </section>

      <section style={{ ...sectionStyle, borderBottom: 'none' }}>
        <h2 style={headingStyle}>Callback log</h2>
        <pre
          data-testid="cb-log"
          style={{
            background: '#111827',
            color: '#d1d5db',
            borderRadius: 8,
            padding: 12,
            fontSize: 12,
            margin: 0,
            minHeight: 48,
          }}
        >
          {JSON.stringify(window.__galleryLog ?? [], null, 2)}
        </pre>
      </section>
    </div>
  );
};

export default DiscoveryComponentsGallery;
