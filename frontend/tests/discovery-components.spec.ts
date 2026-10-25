import { test, expect, type Page } from '@playwright/test';

/**
 * Verification suite for the discovery UI components in
 * src/features/events/components/*.tsx, rendered in isolation with mock data
 * on the dev-only /discovery-components gallery page.
 *
 * Run with the Vite dev server up:  npm run dev && npx playwright test discovery-components
 */

const GALLERY = '/discovery-components';
const SHOTS = 'test-results/gallery';

const CORS = {
  'access-control-allow-origin': 'http://localhost:3000',
  'access-control-allow-credentials': 'true',
  'access-control-allow-headers': '*',
  'access-control-allow-methods': 'GET,POST,PUT,DELETE,OPTIONS',
};

const SEARCH_HITS = [
  {
    id: 42,
    title: 'Lagos Jazz Night',
    category: 'Music',
    start_datetime: new Date(Date.now() + 5 * 86400000).toISOString(),
  },
  {
    id: 77,
    title: 'Jazz Brunch Rooftop',
    category: 'Food',
    start_datetime: new Date(Date.now() + 9 * 86400000).toISOString(),
  },
  {
    id: 91,
    title: 'Jazzercise Live',
    category: 'Sports',
    start_datetime: new Date(Date.now() + 2 * 86400000).toISOString(),
  },
];

async function stubApi(page: Page) {
  await page.route(/events\/public\/search/, async (route) => {
    if (route.request().method() === 'OPTIONS') {
      return route.fulfill({ status: 204, headers: CORS });
    }
    await new Promise((r) => setTimeout(r, 120));
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify({ data: SEARCH_HITS }),
    });
  });
  await page.route(/events\/public\/filters/, (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      headers: CORS,
      body: JSON.stringify({ data: { categories: ['Music', 'Tech'] } }),
    }),
  );
}

async function openGallery(page: Page, query = '') {
  await page.goto(`${GALLERY}${query}`);
  await page.waitForSelector('[data-testid="discovery-gallery"]', { timeout: 20000 });
}

function logEntries(page: Page): Promise<{ name: string; payload: unknown }[]> {
  return page.evaluate(() => window.__galleryLog ?? []);
}

async function logNames(page: Page): Promise<string[]> {
  return (await logEntries(page)).map((e) => e.name);
}

/* ========================================================================== *
 * EventCard
 * ========================================================================== */

test.describe('EventCard', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('displays image, title, organizer, date, venue, price and availability badge', async ({
    page,
  }) => {
    const card = page.locator('[data-testid="eventcard-main"] .event-card');
    await expect(card).toBeVisible();

    await expect(card.locator('img.event-image')).toBeVisible();
    await expect(card.locator('.event-title')).toHaveText('Lagos Jazz Night');
    await expect(card.getByText('Amaka Okonkwo')).toBeVisible(); // organizer
    await expect(card.locator('.meta-row-date')).toContainText(/(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun)/);
    await expect(card.locator('.meta-row').nth(1)).toContainText('Eko Convention Centre');
    await expect(card.locator('.price-value')).toHaveText(/₦5,000/);
    await expect(card.locator('.availability-badge')).toHaveText('160 tickets left');
    await expect(card.locator('.availability-badge')).toHaveClass(/availability-badge--green/);

    await card.screenshot({ path: `${SHOTS}/eventcard-desktop.png` });
  });

  test('is clickable and triggers the onClick callback', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-main"] .event-card');
    await card.locator('.event-title').click();
    const entries = await logEntries(page);
    expect(entries.some((e) => e.name === 'eventCard:click')).toBeTruthy();
    const payload = entries.find((e) => e.name === 'eventCard:click')?.payload as {
      id: number;
    };
    expect(payload.id).toBe(1);
  });

  test('is keyboard operable (Enter activates the card)', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-main"] .event-card');
    await card.focus();
    await page.keyboard.press('Enter');
    expect((await logNames(page)).includes('eventCard:click')).toBeTruthy();
  });

  test('favorite button toggles favorites without opening the event', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-main"] .event-card');
    await card.locator('.event-favorite-btn').click();
    const names = await logNames(page);
    expect(names.includes('eventCard:favorite')).toBeTruthy();
    expect(names.includes('eventCard:click')).toBeFalsy();
  });

  test('shows a skeleton while loading', async ({ page }) => {
    const skeleton = page.locator('[data-testid="eventcard-loading"] .skeleton-card');
    await expect(skeleton).toBeVisible();
    await expect(skeleton).toHaveAttribute('aria-hidden', 'true');
    await expect(skeleton.locator('.skeleton-image-lg')).toBeVisible();
  });

  test('handles edge cases: long title, missing image, broken image, no organizer, sold out', async ({
    page,
  }) => {
    const long = page.locator('[data-testid="eventcard-longtitle"] .event-title');
    await expect(long).toContainText('Extraordinarily Long Annual');
    const longBox = await long.boundingBox();
    const longStyle = await long.evaluate((el) => ({
      lineClamp: getComputedStyle(el).webkitLineClamp,
      overflow: getComputedStyle(el).overflow,
    }));
    expect(longStyle.lineClamp).toBe('2');
    expect(longStyle.overflow).toBe('hidden');
    expect(longBox!.height).toBeLessThan(80);

    const noImage = page.locator('[data-testid="eventcard-noimage"] img.event-image');
    await expect(noImage).toBeVisible();
    expect(await noImage.getAttribute('src')).toMatch(
      /unsplash|data:image/,
    );

    const broken = page.locator('[data-testid="eventcard-brokenimage"] img.event-image');
    await page.waitForTimeout(800);
    expect(await broken.getAttribute('src')).toMatch(/unsplash|data:image/);

    const noOrg = page.locator('[data-testid="eventcard-noorganizer"] .event-card');
    await expect(noOrg).toContainText('Unknown Organizer');

    const soldOut = page.locator('[data-testid="eventcard-soldout"]');
    await expect(soldOut.locator('.availability-badge')).toHaveText('Sold Out');
    await expect(soldOut.locator('.price-value')).toHaveText(/₦5,000/);
  });

  test('renders a good-looking card on mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await openGallery(page);
    const card = page.locator('[data-testid="eventcard-main"] .event-card');
    await expect(card).toBeVisible();
    const box = await card.boundingBox();
    expect(box!.width).toBeLessThanOrEqual(390);
    await card.screenshot({ path: `${SHOTS}/eventcard-mobile.png` });

    const viewport = page.viewportSize()!;
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(1);
  });
});

/* ========================================================================== *
 * FilterSidebar
 * ========================================================================== */

test.describe('FilterSidebar', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await page.setViewportSize({ width: 1280, height: 900 });
    await openGallery(page);
  });

  test('shows all filter options on desktop', async ({ page }) => {
    const aside = page.locator('aside.filter-sidebar');
    await expect(aside).toBeVisible();
    await expect(aside.getByRole('navigation')).toBeVisible();
    await expect(aside.getByText('Category', { exact: true })).toBeVisible();
    await expect(aside.getByText('Date Range')).toBeVisible();
    await expect(aside.getByText('Price', { exact: true })).toBeVisible();
    await expect(aside.getByText('Location')).toBeVisible();
    await expect(aside.getByRole('button', { name: 'Upcoming' })).toBeVisible();
    await expect(aside.getByRole('button', { name: 'This Week' })).toBeVisible();
    await expect(aside.getByRole('button', { name: 'This Month' })).toBeVisible();
    await expect(aside.locator('.price-input__field')).toHaveCount(2);
    await expect(aside.locator('.filter-sidebar__location-input')).toBeVisible();

    await page.locator('[data-testid="filter-sidebar-wrap"]').screenshot({
      path: `${SHOTS}/filter-desktop.png`,
    });
  });

  test('changing date, price and location triggers onChange with merged filters', async ({
    page,
  }) => {
    await page.getByRole('button', { name: 'This Week' }).click();
    let entries = await logEntries(page);
    const dateEntry = entries.filter((e) => e.name === 'filter:change').pop();
    expect(dateEntry?.payload).toMatchObject({ dateRange: 'this week' });

    await page.locator('.price-input__field').first().fill('2000');
    entries = await logEntries(page);
    expect(entries.filter((e) => e.name === 'filter:change').pop()?.payload).toMatchObject({
      dateRange: 'this week',
      minPrice: 2000,
    });

    await page.locator('.filter-sidebar__location-input').fill('Lekki');
    entries = await logEntries(page);
    expect(entries.filter((e) => e.name === 'filter:change').pop()?.payload).toMatchObject({
      location: 'Lekki',
    });

    await expect(page.locator('[data-testid="filter-state"]')).toContainText('Lekki');
  });

  test('category options are visible and selecting one triggers onChange', async ({ page }) => {
    const chip = page.getByRole('button', { name: /Music/ }).first();
    await expect(chip).toBeVisible();
    await chip.click();
    const entries = await logEntries(page);
    expect(entries.filter((e) => e.name === 'filter:change').pop()?.payload).toMatchObject({
      category: expect.stringContaining('music'),
    });
    await expect(chip).toHaveAttribute('aria-pressed', 'true');

    await chip.click();
    const after = await logEntries(page);
    expect(after.filter((e) => e.name === 'filter:change').pop()?.payload).toMatchObject({
      category: undefined,
    });
  });

  test('acts as a collapsible drawer on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await openGallery(page);

    await expect(page.locator('aside.filter-sidebar')).toHaveCount(0);
    const toggle = page.getByRole('button', { name: 'Toggle filters' });
    await expect(toggle).toBeVisible();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');

    await toggle.click();
    const dialog = page.getByRole('dialog', { name: 'Filters' });
    await expect(dialog).toBeVisible();
    await expect(dialog.getByText('Date Range')).toBeVisible();
    await expect(dialog.locator('.filter-sidebar__location-input')).toBeVisible();

    await page.screenshot({ path: `${SHOTS}/filter-mobile-drawer.png` });

    await dialog.getByRole('button', { name: 'Close filters' }).click();
    await expect(page.getByRole('dialog', { name: 'Filters' })).toHaveCount(0);
  });
});

/* ========================================================================== *
 * SearchBar
 * ========================================================================== */

test.describe('SearchBar', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows autocomplete suggestions as the user types', async ({ page }) => {
    const input = page.getByRole('textbox', { name: 'Search events' });
    await input.click();
    await input.type('jazz', { delay: 40 });

    const listbox = page.getByRole('listbox', { name: 'Search suggestions' });
    await expect(listbox).toBeVisible({ timeout: 3000 });
    await expect(listbox.getByRole('option')).toHaveCount(3);
    await expect(listbox).toContainText('Lagos Jazz Night');

    // The dropdown must stay open while the user keeps typing.
    await page.waitForTimeout(1200);
    await expect(listbox).toBeVisible();
    await expect(input).toBeFocused();

    await input.type(' night', { delay: 40 });
    await expect(input).toHaveValue('jazz night');

    await page.locator('[data-testid="search-wrap"]').screenshot({
      path: `${SHOTS}/searchbar-autocomplete.png`,
    });
  });

  test('does not fetch for a single character', async ({ page }) => {
    const input = page.getByRole('textbox', { name: 'Search events' });
    await input.type('j', { delay: 30 });
    await page.waitForTimeout(600);
    await expect(page.getByRole('listbox')).toHaveCount(0);
  });

  test('clicking a suggestion navigates to that event', async ({ page }) => {
    const input = page.getByRole('textbox', { name: 'Search events' });
    await input.type('jazz', { delay: 40 });
    const listbox = page.getByRole('listbox', { name: 'Search suggestions' });
    await expect(listbox).toBeVisible({ timeout: 3000 });

    await listbox.getByRole('option').first().click();
    await expect(page).toHaveURL(/\/events\/42$/);

    const entries = await logEntries(page);
    expect(entries.some((e) => e.name === 'search:select')).toBeTruthy();
  });

  test('keyboard: arrow keys move through suggestions and Escape closes', async ({ page }) => {
    const input = page.getByRole('textbox', { name: 'Search events' });
    await input.type('jazz', { delay: 40 });
    const listbox = page.getByRole('listbox', { name: 'Search suggestions' });
    await expect(listbox).toBeVisible({ timeout: 3000 });

    await page.keyboard.press('ArrowDown');
    await expect(listbox.getByRole('option').nth(1)).toHaveAttribute('aria-selected', 'true');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/events\/77$/);
  });
});

/* ========================================================================== *
 * TicketTierCard
 * ========================================================================== */

test.describe('TicketTierCard', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows tier name, price, quantity available and Select button', async ({ page }) => {
    const card = page.locator('[data-testid="tier-normal"]');
    await expect(card.locator('.ticket-tier-card__name')).toHaveText('General Admission');
    await expect(card.locator('.ticket-tier-card__price')).toHaveText(/₦5,000/);
    await expect(card.locator('.availability-badge')).toHaveText('160 tickets left');
    await expect(card.locator('.ticket-tier-card__select-button')).toBeEnabled();

    await card.locator('.ticket-tier-card__select-button').click();
    expect((await logNames(page)).includes('tier:select')).toBeTruthy();

    await page.locator('#sec-tiers').screenshot({ path: `${SHOTS}/ticket-tiers.png` });
  });

  test('low availability shows amber badge, sold out disables the button', async ({ page }) => {
    const low = page.locator('[data-testid="tier-low"]');
    await expect(low.locator('.availability-badge')).toHaveText('8 tickets left');
    await expect(low.locator('.availability-badge')).toHaveClass(/availability-badge--amber/);

    const soldOut = page.locator('[data-testid="tier-soldout"]');
    await expect(soldOut.locator('.availability-badge')).toHaveText('Sold Out');
    await expect(soldOut.locator('.ticket-tier-card__select-button')).toBeDisabled();

    const loading = page.locator('[data-testid="tier-loading"]');
    await expect(loading.locator('.ticket-tier-card__select-loading')).toHaveText(
      'Selecting...',
    );
  });
});

/* ========================================================================== *
 * EventGrid
 * ========================================================================== */

async function gridColumns(page: Page): Promise<number> {
  const value = await page
    .locator('.event-grid')
    .evaluate((el) => getComputedStyle(el).gridTemplateColumns);
  return value.split(' ').filter(Boolean).length;
}

test.describe('EventGrid', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
  });

  test('renders multiple EventCards in a responsive grid (desktop 3 / tablet 2 / mobile 1)', async ({
    page,
  }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await openGallery(page);
    await expect(page.locator('.event-grid .event-card')).toHaveCount(12);
    expect(await gridColumns(page)).toBe(3);
    await page.screenshot({ path: `${SHOTS}/grid-desktop.png`, fullPage: false });

    await page.setViewportSize({ width: 900, height: 900 });
    await openGallery(page);
    expect(await gridColumns(page)).toBe(2);
    await page.screenshot({ path: `${SHOTS}/grid-tablet.png`, fullPage: false });

    await page.setViewportSize({ width: 390, height: 844 });
    await openGallery(page);
    expect(await gridColumns(page)).toBe(1);
    const overflow = await page.evaluate(
      () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
    );
    expect(overflow).toBeLessThanOrEqual(1);
    await page.screenshot({ path: `${SHOTS}/grid-mobile.png`, fullPage: false });
  });

  test('shows loading skeleton cards while fetching', async ({ page }) => {
    await openGallery(page, '?grid=loading');
    const skeletons = page.locator('.event-grid .skeleton-card');
    await expect(skeletons).toHaveCount(6);
    await expect(page.locator('.event-grid')).toHaveAttribute('aria-busy', 'true');
    await page.locator('.event-grid').screenshot({ path: `${SHOTS}/grid-loading.png` });
  });

  test('shows an empty state when no events match filters', async ({ page }) => {
    await openGallery(page, '?grid=empty');
    await expect(page.locator('.empty-state__title')).toHaveText('No events found');
    await expect(page.locator('.empty-state__description')).toContainText(
      'Try adjusting your search or filters.',
    );
    await page.locator('.event-grid').screenshot({ path: `${SHOTS}/grid-empty.png` });
  });

  test('supports infinite scroll via the onLoadMore callback', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await openGallery(page, '?grid=hasmore');
    await expect(page.locator('.event-grid .event-card')).toHaveCount(12);

    // Scroll to the bottom; the grid must request more data on its own.
    for (let i = 0; i < 12; i++) {
      await page.evaluate(() => window.scrollBy(0, 700));
      await page.waitForTimeout(150);
      if ((await logNames(page)).includes('grid:loadMore')) break;
    }
    expect((await logNames(page)).includes('grid:loadMore')).toBeTruthy();
    await expect(page.locator('.event-grid .event-card')).toHaveCount(24);
  });

  test('load more control is keyboard reachable when the observer cannot fire', async ({
    page,
  }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await openGallery(page, '?grid=hasmore');
    const button = page.getByRole('button', { name: /load more/i }).first();
    await expect(button).toBeVisible();
    await button.focus();
    await page.keyboard.press('Enter');
    expect((await logNames(page)).includes('grid:loadMore')).toBeTruthy();
  });
});

/* ========================================================================== *
 * PricingWindow
 * ========================================================================== */

test.describe('PricingWindow', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows current price, next price and a countdown timer that updates', async ({
    page,
  }) => {
    const win = page.locator('[data-testid="pricing-wrap"] .pricing-window');
    await expect(win.locator('.pricing-window__value').first()).toHaveText(/₦12,500/);
    await expect(win.getByText('Next Price')).toBeVisible();
    await expect(win.locator('.pricing-window__value').nth(1)).toHaveText(/₦17,500/);
    await expect(win.locator('.pricing-window__countdown-text')).toContainText(
      /Price changes in \d+ days/,
    );
    await win.screenshot({ path: `${SHOTS}/pricing-window.png` });
  });

  test('countdown ticks down as the price change approaches', async ({ page }) => {
    await openGallery(page, '?pricing=tick');
    const countdown = page.locator('.pricing-window__countdown-text');
    await expect(countdown).toContainText('Price changes in');

    const first = await countdown.textContent();
    await page.waitForTimeout(2500);
    const second = await countdown.textContent();
    expect(second).not.toBe(first);

    await expect(countdown).toHaveText('Price changes Now', { timeout: 15000 });
  });

  test('hides the next-price block when there is no upcoming change', async ({ page }) => {
    await openGallery(page, '?pricing=none');
    await expect(page.locator('.pricing-window__value').first()).toBeVisible();
    await expect(page.getByText('Next Price')).toHaveCount(0);
  });
});

/* ========================================================================== *
 * OrganizerCard
 * ========================================================================== */

test.describe('OrganizerCard', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test('shows avatar, name and bio and is clickable', async ({ page }) => {
    const card = page.locator('[data-testid="organizer-full"] .organizer-card');
    await expect(card.locator('.organizer-card__avatar-image')).toBeVisible();
    await expect(card.locator('.organizer-card__name')).toHaveText('Amaka Okonkwo');
    await expect(card.locator('.organizer-card__bio')).toContainText('Award-winning producer');

    await card.click();
    expect((await logNames(page)).includes('organizer:click')).toBeTruthy();

    await card.focus();
    await page.keyboard.press('Enter');
    expect((await logNames(page)).filter((n) => n === 'organizer:click').length).toBeGreaterThanOrEqual(2);

    await page.locator('#sec-organizer').screenshot({ path: `${SHOTS}/organizer-cards.png` });
  });

  test('handles missing avatar, name and bio', async ({ page }) => {
    const minimal = page.locator('[data-testid="organizer-minimal"] .organizer-card');
    await expect(minimal.locator('.organizer-card__avatar-placeholder')).toBeVisible();
    await expect(minimal.locator('.organizer-card__name')).toHaveText('Tunde Adeyemi');
    await expect(minimal.locator('.organizer-card__bio')).toHaveCount(0);

    const longBio = page.locator('[data-testid="organizer-longbio"] .organizer-card');
    const bioText = (await longBio.locator('.organizer-card__bio').textContent()) ?? '';
    expect(bioText.length).toBeLessThanOrEqual(103);
    if (bioText.length <= 100) {
      expect(bioText.endsWith('...')).toBeFalsy();
    }

    const broken = page.locator('[data-testid="organizer-brokenavatar"] .organizer-card');
    await page.waitForTimeout(600);
    await expect(broken.locator('.organizer-card__avatar-placeholder')).toBeVisible();
  });
});

/* ========================================================================== *
 * AvailabilityBadge
 * ========================================================================== */

test.describe('AvailabilityBadge', () => {
  test.beforeEach(async ({ page }) => {
    await stubApi(page);
    await openGallery(page);
  });

  test("shows correct color and text: green '45 tickets left' vs 'Sold Out'", async ({
    page,
  }) => {
    const green = page.locator('[data-testid="badge-green"] .availability-badge');
    await expect(green).toHaveText('45 tickets left');
    await expect(green).toHaveClass(/availability-badge--green/);

    const amber = page.locator('[data-testid="badge-amber"] .availability-badge');
    await expect(amber).toHaveText('12 tickets left');
    await expect(amber).toHaveClass(/availability-badge--amber/);

    const red = page.locator('[data-testid="badge-red"] .availability-badge');
    await expect(red).toHaveText('3 tickets left');
    await expect(red).toHaveClass(/availability-badge--red/);

    const soldOut = page.locator('[data-testid="badge-soldout"] .availability-badge');
    await expect(soldOut).toHaveText('Sold Out');
    await expect(soldOut).toHaveClass(/availability-badge--(red|sold-out)/);

    await page.locator('#sec-badges').screenshot({ path: `${SHOTS}/availability-badges.png` });
  });

  test('does not claim Sold Out while tickets remain', async ({ page }) => {
    const critical = page.locator('[data-testid="badge-critical"] .availability-badge');
    await expect(critical).toHaveText('1 tickets left');
    await expect(critical).toHaveClass(/availability-badge--red/);

    const zeroTotal = page.locator('[data-testid="badge-zerototal"] .availability-badge');
    await expect(zeroTotal).toHaveText('Sold Out');
  });
});
