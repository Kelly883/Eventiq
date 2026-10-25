import { test, expect, type Page } from '@playwright/test';

/**
 * Verification suite for the five dashboard components
 * (MetricCard, EventCard, EventDetailWidget, SalesActivityFeed,
 * DashboardHeader), rendered in isolation with mock data on the dev-only
 * /dashboard-components gallery page.
 *
 * Run with the Vite dev server up:
 *   npm run dev && npx playwright test dashboard-components
 *
 * The API stubs below mirror the REAL backend response shapes
 * (app/Features/Dashboard/Controllers/DashboardController.php) so the
 * frontend normalization layer is exercised, not bypassed.
 */

const GALLERY = '/dashboard-components';
const SHOTS = 'test-results/dashboard-gallery';

const CORS = {
  'access-control-allow-origin': 'http://localhost:3000',
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': '*',
  'access-control-allow-methods': 'GET,POST,PUT,DELETE,OPTIONS',
};

const iso = (msOffset: number) => new Date(Date.now() + msOffset).toISOString();

/* -------------------------------------------------------------------------- */
/*                                   Fixtures                                  */
/* -------------------------------------------------------------------------- */

const METRICS_PAYLOAD = {
  success: true,
  metrics: {
    totalEvents: 24,
    eventsPublished: 18,
    totalTicketsSold: 1204,
    totalRevenue: 4250000,
    totalPageViews: 18930,
  },
  trends: {
    ticketsSold: { direction: 'up', percentageChange: 12.5, delta: 134 },
    revenue: { direction: 'up', percentageChange: 8.4, delta: 329500 },
    pageViews: { direction: 'down', percentageChange: -3.2, delta: -620 },
  },
};

const EVENTS_PAYLOAD = {
  success: true,
  events: [
    {
      id: 501,
      title: 'Lagos Jazz Festival 2026',
      description: 'Three nights of world-class jazz on the Lagos waterfront.',
      status: 'published',
      startDatetime: iso(9 * 86400000),
      endDatetime: iso(11 * 86400000),
      venueName: 'Eko Convention Centre',
      bannerUrl: 'https://example.test/banner.jpg',
      organizerName: 'Naija Live Sound',
      capacity: 1000,
      ticketsSold: 812,
      revenue: 4250000,
    },
    {
      id: 502,
      title: 'Tech Summit Lagos',
      description: 'A one-day summit for builders.',
      status: 'published',
      startDatetime: iso(20 * 86400000),
      endDatetime: iso(20 * 86400000),
      venueName: 'Landmark Event Centre',
      bannerUrl: null,
      organizerName: 'Naija Live Sound',
      capacity: 500,
      ticketsSold: 120,
      revenue: 950000,
    },
  ],
  pagination: { currentPage: 1, lastPage: 1, perPage: 50, total: 2 },
};

const EVENT_DETAIL_PAYLOAD = {
  success: true,
  event: {
    id: 501,
    title: 'Lagos Jazz Festival 2026',
    description: 'Three nights of world-class jazz on the Lagos waterfront.',
    status: 'published',
    startDatetime: iso(9 * 86400000),
    endDatetime: iso(11 * 86400000),
    venueName: 'Eko Convention Centre',
    bannerUrl: 'https://example.test/banner.jpg',
    capacity: 1000,
    tiers: [
      { id: 't1', name: 'General Admission', price: 5000, quantity: 500, soldCount: 420, isActive: true },
      { id: 't2', name: 'VIP', price: 15000, quantity: 200, soldCount: 180, isActive: true },
      { id: 't3', name: 'Early Bird', price: 3500, quantity: 300, soldCount: 212, isActive: false },
    ],
    pricingTimeline: [
      { id: 'w1', label: 'Early Bird', price: 3500, date: iso(-30 * 86400000), endDate: iso(-10 * 86400000) },
      { id: 'w2', label: 'Regular', price: 5000, date: iso(-9 * 86400000), endDate: iso(3 * 86400000) },
      { id: 'w3', label: 'Last Minute', price: 7500, date: iso(4 * 86400000), endDate: iso(9 * 86400000) },
    ],
    metrics: {
      totalRevenue: 4250000,
      totalTicketsSold: 812,
      pageViews: 18930,
      conversionRate: 0.043,
      averageTicketPrice: 5233,
    },
  },
};

const ACTIVITY_PAYLOAD = {
  success: true,
  enabled: true,
  activities: [
    {
      id: 'a-3',
      type: 'ticket_sale',
      message: 'Ticket sale: 2 ticket(s) for $15000',
      timestamp: iso(-2 * 60000),
      eventId: '501',
      eventName: 'Lagos Jazz Festival 2026',
      eventInitials: 'LJ',
      tierId: 't2',
      tier: 'VIP',
      quantity: 2,
      price: 7500,
      totalAmount: 15000,
    },
    {
      id: 'a-2',
      type: 'ticket_sale',
      message: 'Ticket sale: 5 ticket(s) for $25000',
      timestamp: iso(-30 * 60000),
      eventId: '502',
      eventName: 'Tech Summit Lagos',
      eventInitials: 'TS',
      tierId: 't1',
      tier: 'General Admission',
      quantity: 5,
      price: 5000,
      totalAmount: 25000,
    },
    {
      id: 'a-1',
      type: 'ticket_sale',
      message: 'Ticket sale: 1 ticket(s) for $3500',
      timestamp: iso(-90 * 60000),
      eventId: '501',
      eventName: 'Lagos Jazz Festival 2026',
      eventInitials: 'LJ',
      tierId: 't3',
      tier: 'Early Bird',
      quantity: 1,
      price: 3500,
      totalAmount: 3500,
    },
  ],
};

/** Payload returned on the second (polled) fetch: one brand-new sale on top. */
const ACTIVITY_POLLED_PAYLOAD = {
  success: true,
  enabled: true,
  activities: [
    {
      id: 'a-new',
      type: 'ticket_sale',
      message: 'Ticket sale: 4 ticket(s) for $20000',
      timestamp: new Date().toISOString(),
      eventId: '501',
      eventName: 'Lagos Jazz Festival 2026',
      eventInitials: 'LJ',
      tierId: 't1',
      tier: 'General Admission',
      quantity: 4,
      price: 5000,
      totalAmount: 20000,
    },
    ...ACTIVITY_PAYLOAD.activities,
  ],
};

const EMPTY_ACTIVITY_PAYLOAD = { success: true, enabled: true, activities: [] };

/* -------------------------------------------------------------------------- */
/*                                    Helpers                                  */
/* -------------------------------------------------------------------------- */

type StubOptions = {
  activityPayload?: any;
  emptyMetrics?: boolean;
  emptyEvents?: boolean;
  emptyDetail?: boolean;
  activityError?: boolean;
};

async function stubApi(page: Page, options: StubOptions = {}) {
  const activityCalls = { count: 0 };

  await page.route(/organizer\/dashboard\/metrics/, async (route) => {
    if (route.request().method() === 'OPTIONS') {
      return route.fulfill({ status: 204, headers: CORS });
    }
    await new Promise((r) => setTimeout(r, 80));
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify(options.emptyMetrics ? { success: true, metrics: {}, trends: {} } : METRICS_PAYLOAD),
    });
  });

  await page.route(/organizer\/dashboard\/events\/[^/]+/, async (route) => {
    if (route.request().method() === 'OPTIONS') {
      return route.fulfill({ status: 204, headers: CORS });
    }
    await new Promise((r) => setTimeout(r, 80));
    if (options.emptyDetail) {
      return route.fulfill({
        status: 404,
        contentType: 'application/json',
        headers: CORS,
        body: JSON.stringify({ message: 'Event not found' }),
      });
    }
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify(EVENT_DETAIL_PAYLOAD),
    });
  });

  await page.route(/organizer\/dashboard\/events(\?|$)/, async (route) => {
    if (route.request().method() === 'OPTIONS') {
      return route.fulfill({ status: 204, headers: CORS });
    }
    await new Promise((r) => setTimeout(r, 80));
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify(options.emptyEvents ? { success: true, events: [] } : EVENTS_PAYLOAD),
    });
  });

  await page.route(/organizer\/dashboard\/activity-feed/, async (route) => {
    if (route.request().method() === 'OPTIONS') {
      return route.fulfill({ status: 204, headers: CORS });
    }
    await new Promise((r) => setTimeout(r, 80));
    if (options.activityError) {
      return route.fulfill({
        status: 500,
        contentType: 'application/json',
        headers: CORS,
        body: JSON.stringify({ message: 'boom' }),
      });
    }
    activityCalls.count += 1;
    const payload =
      options.activityPayload ??
      (activityCalls.count > 1 ? ACTIVITY_POLLED_PAYLOAD : ACTIVITY_PAYLOAD);
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify(payload),
    });
  });

  return activityCalls;
}

async function openGallery(page: Page) {
  await page.goto(GALLERY);
  await page.waitForSelector('[data-testid="dashboard-gallery"]', { timeout: 20000 });
}

function logEntries(page: Page): Promise<{ name: string; payload: any }[]> {
  return page.evaluate(() => window.__dashboardGalleryLog ?? []);
}

async function logNames(page: Page): Promise<string[]> {
  return (await logEntries(page)).map((e) => e.name);
}

/* ========================================================================== *
 * MetricCard
 * ========================================================================== */

test.describe('MetricCard', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('displays label, value and trend indicator with the right colours', async ({ page }) => {
    const card = page.locator('[data-testid="metric-total-revenue"]');

    await expect(card.getByText('Total Revenue', { exact: true })).toBeVisible();
    await expect(card.getByText('₦4,250,000')).toBeVisible();

    const trend = card.locator('[aria-live="polite"]');
    await expect(trend).toHaveText(/\+12\.5%/);
    await expect(trend).toHaveAttribute('aria-label', /Trending up by \+12\.5%/);
    await expect(trend).toHaveClass(/text-emerald-700/);
    await expect(trend).toHaveClass(/bg-emerald-50/);

    await card.screenshot({ path: `${SHOTS}/metriccard-desktop.png` });
  });

  test('formats non-currency metrics and colours down/neutral trends', async ({ page }) => {
    const events = page.locator('[data-testid="metric-active-events"]');
    await expect(events.getByText('24', { exact: true })).toBeVisible();
    await expect(events.locator('[aria-live="polite"]')).toHaveClass(/text-slate-500/);

    const tickets = page.locator('[data-testid="metric-tickets-sold"]');
    await expect(tickets.getByText('1,204')).toBeVisible();
    await expect(tickets.locator('[aria-live="polite"]')).toHaveText(/\+12\.5%/);
    await expect(tickets.locator('[aria-live="polite"]')).toHaveClass(/text-emerald-700/);
  });

  test('renders the loading skeleton instead of a value while data loads', async ({ page }) => {
    const loading = page.locator('[data-testid="metric-loading"]');

    // The card switches to a skeleton: pulsing bars for label, value, trend (3 total), no value, aria-busy.
    await expect(loading.locator('.animate-pulse')).toHaveCount(3);
    await expect(loading.locator('[aria-busy="true"]')).toHaveCount(1);
    await expect(loading.getByText('₦4,250,000')).toHaveCount(0);
    await expect(loading.locator('[aria-live="polite"]')).toHaveCount(0);

    await loading.screenshot({ path: `${SHOTS}/metriccard-loading.png` });
  });

  test('falls back to a placeholder when a metric has no value', async ({ page }) => {
    const empty = page.locator('[data-testid="metric-empty"]');
    await expect(empty.getByText('—')).toBeVisible();
    await expect(empty.locator('[aria-live="polite"]')).toHaveText(/0%/);
  });
});

/* ========================================================================== *
 * EventCard
 * ========================================================================== */

test.describe('EventCard', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows thumbnail, title, date, status badge, progress, revenue and attendees', async ({
    page,
  }) => {
    const card = page.locator('[data-testid="eventcard-main"]');

    await expect(card.locator('img')).toBeVisible();
    await expect(card.locator('h3')).toHaveText('Lagos Jazz Festival 2026');
    await expect(card.getByText('Eko Convention Centre')).toBeVisible();

    // Status badge
    await expect(card.getByText('Active', { exact: true })).toBeVisible();

    // Progress bar
    const progress = card.locator('[role="progressbar"]');
    await expect(progress).toHaveAttribute('aria-valuenow', '81');

    // Revenue + attendees
    await expect(card.getByText('₦4,250,000')).toBeVisible();
    await expect(card.getByText('812', { exact: true })).toBeVisible();

    await card.screenshot({ path: `${SHOTS}/eventcard-desktop.png` });
  });

  test('triggers the correct callbacks from the card actions', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-main"]');

    await card.getByRole('button', { name: 'View analytics' }).click();
    await card.getByRole('button', { name: 'Manage inventory' }).click();
    await card.getByRole('button', { name: 'Edit event' }).click();

    const entries = await logEntries(page);
    const byName = (name: string) => entries.find((e) => e.name === name)?.payload;

    expect(byName('eventCard:viewAnalytics')).toMatchObject({
      eventId: 501,
      path: '/organizer/events/501/analytics',
    });
    expect(byName('eventCard:manageInventory')).toMatchObject({
      eventId: 501,
      path: '/organizer/events/501/inventory',
    });
    expect(byName('eventCard:editEvent')).toMatchObject({
      eventId: 501,
      path: '/organizer/events/501/edit',
    });
  });

  test('tapping the card expands the EventDetailWidget', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-main"]');
    const toggle = card.getByRole('button', { name: /Details|Hide/ });

    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('[data-testid="eventcard-main"] #event-details-501')).toHaveCount(0);

    await toggle.click();

    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#event-details-501')).toBeVisible();
    await expect(page.locator('#event-details-501').getByText('Event Details')).toBeVisible();

    // Collapsing hides it again.
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('#event-details-501')).toHaveCount(0);
  });

  test('handles a long title and a missing banner image', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-edge"]');

    await expect(card.locator('h3')).toHaveText(
      'Annual West African Technology Innovation Summit & Entrepreneurship Networking Retreat 2026',
    );
    await expect(card.getByText('Draft', { exact: true })).toBeVisible();
    await expect(card.getByText('Venue TBA')).toBeVisible();
    await expect(card.getByText('Date TBA')).toBeVisible();
    await expect(card.getByText('₦0')).toBeVisible();
    // No image element is rendered when there is no banner.
    await expect(card.locator('img')).toHaveCount(0);

    await card.screenshot({ path: `${SHOTS}/eventcard-edge.png` });
  });
});

/* ========================================================================== *
 * EventDetailWidget
 * ========================================================================== */

test.describe('EventDetailWidget', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows event information, tier breakdown and pricing timeline', async ({ page }) => {
    const widget = page.locator('[data-testid="eventdetail-standalone"]');

    // Event information
    await expect(widget.getByText('Event Information')).toBeVisible();
    await expect(widget.getByText('Eko Convention Centre')).toBeVisible();
    await expect(widget.getByText('501', { exact: true })).toBeVisible();

    // Tier breakdown
    await expect(widget.getByText('Tier Breakdown')).toBeVisible();
    await expect(widget.getByText('General Admission')).toBeVisible();
    await expect(widget.getByText('420/500')).toBeVisible();
    await expect(widget.getByText('84%', { exact: true })).toBeVisible();
    await expect(widget.getByText('VIP')).toBeVisible();
    await expect(widget.getByText('180/200')).toBeVisible();

    // Pricing timeline
    await expect(widget.getByText('Pricing Timeline')).toBeVisible();
    await expect(widget.getByText(/Early Bird/).first()).toBeVisible();
    await expect(widget.getByText('₦3,500')).toBeVisible();
    await expect(widget.getByText('₦7,500')).toBeVisible();

    await widget.screenshot({ path: `${SHOTS}/eventdetail-desktop.png` });
  });

  test('navigates via the action buttons', async ({ page }) => {
    const widget = page.locator('[data-testid="eventdetail-standalone"]');

    await widget.getByRole('button', { name: 'View full analytics' }).click();
    await widget.getByRole('button', { name: 'Manage inventory' }).click();
    await widget.getByRole('button', { name: 'Edit event' }).click();

    const entries = await logEntries(page);
    const paths = entries
      .filter((e) => e.name.startsWith('eventCard:'))
      .map((e) => e.payload.path);

    expect(paths).toContain('/organizer/events/501/analytics');
    expect(paths).toContain('/organizer/events/501/inventory');
    expect(paths).toContain('/organizer/events/501/edit');
  });

  test('shows an empty state when the event no longer exists', async ({ page }) => {
    await page.unroute(/organizer\/dashboard\/events\/[^/]+/);
    await stubApi(page, { emptyDetail: true });
    await page.reload();
    await page.waitForSelector('[data-testid="dashboard-gallery"]');

    const widget = page.locator('[data-testid="eventdetail-standalone"]');
    await expect(widget.getByText(/no longer available/i)).toBeVisible();
  });
});

/* ========================================================================== *
 * SalesActivityFeed
 * ========================================================================== */

test.describe('SalesActivityFeed', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows activity items with event badge, tier, quantity, price and timestamp', async ({
    page,
  }) => {
    const feed = page.locator('[data-testid="activity-host"]');
    const rows = feed.locator('ul > li');

    await expect(rows).toHaveCount(3);

    const first = rows.first();
    await expect(first.getByText('LJ')).toBeVisible();
    await expect(first.getByText('Lagos Jazz Festival 2026')).toBeVisible();
    await expect(first.getByText('VIP')).toBeVisible();
    await expect(first.getByText('2 ×')).toBeVisible();
    // Target desktop price (hidden sm:block) to avoid strict mode violation with mobile duplicate
    await expect(first.locator('p.font-semibold.text-slate-800').first()).toHaveText('₦7,500');
    await expect(first.locator('time')).toHaveAttribute('datetime', /Z$/);

    await feed.screenshot({ path: `${SHOTS}/activity-desktop.png` });
  });

  test('filters by event', async ({ page }) => {
    const feed = page.locator('[data-testid="activity-host"]');
    await feed.locator('#filter-event').selectOption('502');
    await page.waitForTimeout(1000);

    const rows = feed.locator('ul > li');
    await expect(rows).toHaveCount(1);
    await expect(rows.first().getByText('Tech Summit Lagos')).toBeVisible();
    await expect(rows.first().getByText('5 ×')).toBeVisible();

    // The filter is mirrored into the URL so the view is shareable.
    await expect(page).toHaveURL(/event=502/);

    await feed.getByRole('button', { name: 'Clear all filters' }).first().click();
    await page.waitForTimeout(1000);
    await expect(rows).toHaveCount(3);
  });

  test('filters by tier', async ({ page }) => {
    const feed = page.locator('[data-testid="activity-host"]');
    const tierSelect = feed.locator('#filter-tier');

    await expect(tierSelect.locator('option')).toHaveCount(4); // All + 3 tiers
    await tierSelect.selectOption({ label: 'General Admission' });
    await page.waitForTimeout(1000);

    const rows = feed.locator('ul > li');
    await expect(rows).toHaveCount(1);
    await expect(rows.first().getByText('Tech Summit Lagos')).toBeVisible();
  });

  test('filters by date range', async ({ page }) => {
    const feed = page.locator('[data-testid="activity-host"]');

    // Everything is inside the last two hours.
    const today = new Date();
    const from = new Date(today.getTime() - 24 * 3600 * 1000).toISOString().slice(0, 10);
    await feed.locator('#filter-date-from').fill(from);
    await page.waitForTimeout(1000);
    await expect(feed.locator('ul > li')).toHaveCount(3);

    // A window that starts "now" excludes every historical sale.
    const now = new Date().toISOString().slice(0, 10);
    await feed.locator('#filter-date-from').fill(now);
    await feed.locator('#filter-date-to').fill(now);
    await page.waitForTimeout(1000);
    await expect(feed.locator('ul > li')).toHaveCount(0);
    await expect(feed.getByText('No matching activity')).toBeVisible();
  });

  test('exports the filtered activity to CSV', async ({ page }) => {
    const feed = page.locator('[data-testid="activity-host"]');

    const downloadPromise = page.waitForEvent('download');
    await feed.getByRole('button', { name: 'Export Activity to CSV' }).click();
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toMatch(/^activity-export-\d{4}-\d{2}-\d{2}\.csv$/);

    const stream = await download.createReadStream();
    const chunks: Buffer[] = [];
    for await (const chunk of stream) chunks.push(chunk as Buffer);
    const csv = Buffer.concat(chunks).toString('utf-8');

    // Component outputs BOM + RFC 4180 quoted fields; normalize for assertion
    const normalized = csv.replace(/^\uFEFF/, '').replace(/"/g, '');
    expect(normalized).toContain('Timestamp,Event,Tier,Quantity,Price');
    expect(normalized).toContain('Lagos Jazz Festival 2026');
    expect(normalized).toContain('VIP');
    expect(normalized.split('\r\n').length).toBeGreaterThanOrEqual(4);
  });

  test('auto-updates every 30 seconds and puts new items on top', async ({ page }) => {
    test.setTimeout(90_000);

    const feed = page.locator('[data-testid="activity-host"]');
    const rows = feed.locator('ul > li');
    await expect(rows).toHaveCount(3);

    // The poll fires at 30s; the stub then returns one extra sale.
    await expect
      .poll(async () => rows.count(), { timeout: 60_000, intervals: [1000] })
      .toBeGreaterThan(3);

    await expect(rows.first().getByText('Lagos Jazz Festival 2026')).toBeVisible();
    await expect(rows.first().getByText('4 ×')).toBeVisible();
    // Target desktop price (hidden sm:block) to avoid strict mode violation with mobile duplicate
    await expect(rows.first().locator('p.font-semibold.text-slate-800').first()).toHaveText('₦5,000');
  });

  test('shows an empty state when there are no sales', async ({ page }) => {
    await page.unroute(/organizer\/dashboard\/activity-feed/);
    await stubApi(page, { activityPayload: EMPTY_ACTIVITY_PAYLOAD });
    await page.reload();
    await page.waitForSelector('[data-testid="dashboard-gallery"]');

    const feed = page.locator('[data-testid="activity-host"]');
    await expect(feed.getByText('No sales activity yet')).toBeVisible();
    await expect(feed.getByRole('button', { name: 'Export Activity to CSV' })).toBeDisabled();
  });

  test('shows an error state when the feed cannot be loaded', async ({ page }) => {
    await page.unroute(/organizer\/dashboard\/activity-feed/);
    await stubApi(page, { activityError: true });
    await page.reload();
    await page.waitForSelector('[data-testid="dashboard-gallery"]');

    const feed = page.locator('[data-testid="activity-host"]');
    await expect(feed.getByText('Activity feed unavailable')).toBeVisible();
    await expect(feed.getByRole('button', { name: 'Retry' })).toBeVisible();
  });
});

/* ========================================================================== *
 * DashboardHeader
 * ========================================================================== */

test.describe('DashboardHeader', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('renders the create button, filters, date range picker and live indicator', async ({
    page,
  }) => {
    const header = page.locator('[data-testid="header-host"]');

    await expect(header.getByRole('button', { name: 'Create new event' })).toBeVisible();
    await expect(header.getByRole('combobox').first()).toBeVisible();
    await expect(header.locator('#header-date-range')).toBeVisible();
    await expect(header.getByLabel('Live')).toBeVisible();
    await expect(header.getByText(/Good (morning|afternoon|evening)/)).toBeVisible();

    await header.screenshot({ path: `${SHOTS}/header-desktop.png` });
  });

  test('fires the filter and date range callbacks', async ({ page }) => {
    const header = page.locator('[data-testid="header-host"]');

    await header.locator('#header-filter').selectOption('concert');
    await header.locator('#header-date-range').selectOption('30d');

    const entries = await logEntries(page);
    expect(entries.find((e) => e.name === 'header:filterChange')?.payload).toEqual({
      filter: 'concert',
    });

    const range = entries.find((e) => e.name === 'header:dateRangeChange')?.payload;
    expect(range).toBeTruthy();
    const spanDays =
      (new Date(range.to).getTime() - new Date(range.from).getTime()) / (1000 * 60 * 60 * 24);
    expect(spanDays).toBeGreaterThanOrEqual(29);
    expect(spanDays).toBeLessThanOrEqual(31);
  });

  test('fires the create event callback', async ({ page }) => {
    await page.locator('[data-testid="header-host"]').getByRole('button', { name: 'Create new event' }).click();
    const entries = await logEntries(page);
    expect(entries.find((e) => e.name === 'header:createEvent')?.payload).toEqual({
      path: '/organizer/events/new',
    });
  });
});

/* ========================================================================== *
 * Responsive + empty states
 * ========================================================================== */

test.describe('Responsive layout', () => {
  test.use({ viewport: { width: 375, height: 667 } });

  test('components stack and stay readable on a mobile viewport', async ({ page }) => {
    await stubApi(page);
    await openGallery(page);

    // No horizontal overflow anywhere on the page.
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(1);

    const header = page.locator('[data-testid="header-host"]');
    await expect(header.getByRole('button', { name: 'Create new event' })).toBeVisible();
    await expect(header.locator('#header-filter')).toBeVisible();

    const card = page.locator('[data-testid="eventcard-main"]');
    await expect(card.getByText('₦4,250,000')).toBeVisible();
    await expect(card.locator('[role="progressbar"]')).toBeVisible();

    await page.screenshot({ path: `${SHOTS}/gallery-mobile.png`, fullPage: true });
  });
});

test.describe('Empty states', () => {
  test('the feed and the detail widget degrade gracefully', async ({ page }) => {
    await stubApi(page, { emptyMetrics: true, emptyEvents: true, activityPayload: EMPTY_ACTIVITY_PAYLOAD });
    await openGallery(page);

    const feed = page.locator('[data-testid="activity-host"]');
    await expect(feed.getByText('No sales activity yet')).toBeVisible();

    await expect(page.locator('[data-testid="metric-row"]').getByText('₦0')).toBeVisible();

    await page.screenshot({ path: `${SHOTS}/gallery-empty.png`, fullPage: true });
  });
});
