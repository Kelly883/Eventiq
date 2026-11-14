import { test, expect, type Page } from '@playwright/test';

/**
 * Verification suite for the events-calendar UI components
 * (src/features/events-calendar/components), rendered in isolation with mock
 * data on the dev-only /calendar-components gallery page.
 *
 * Run with the Vite dev server up:  npm run dev && npx playwright test --project=firefox calendar-components
 */

const GALLERY = '/calendar-components';
const SHOTS = 'test-results/calendar-gallery';

type LogEntry = { name: string; payload: unknown };

async function openGallery(page: Page) {
  await page.goto(GALLERY);
  await page.waitForSelector('[data-testid="section-calendar-grid"]', {
    timeout: 20000,
  });
}

async function getLogs(page: Page): Promise<LogEntry[]> {
  return (await page.evaluate(() =>
    JSON.stringify(window.__galleryLog ?? [])
  )) as unknown as LogEntry[];
}

function findLog(
  logs: LogEntry[],
  name: string,
  payload?: unknown
): LogEntry | undefined {
  return logs.find(
    (e) =>
      e.name === name &&
      (payload === undefined ||
        JSON.stringify(e.payload) === JSON.stringify(payload))
  );
}

test.describe('CalendarGrid', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('renders a month view with color-coded availability indicators', async ({
    page,
  }) => {
    const green = page.getByRole('gridcell', {
      name: /1 event on 2026-03-14/,
    });
    await expect(green).toBeVisible();
    await expect(green).toHaveCSS('border-left-color', 'rgb(16, 185, 129)');

    const amber = page.getByRole('gridcell', {
      name: /1 event on 2026-03-17/,
    });
    await expect(amber).toHaveCSS('border-left-color', 'rgb(245, 158, 11)');

    const red = page.getByRole('gridcell', {
      name: /1 event on 2026-03-20/,
    });
    await expect(red).toHaveCSS('border-left-color', 'rgb(239, 68, 68)');

    await expect(
      page.getByRole('gridcell', { name: /2 events on 2026-03-28/ })
    ).toBeVisible();

    await expect(page.locator('.rbc-month-view')).toBeVisible();
    await page.locator('#sec-calendar').screenshot({
      path: `${SHOTS}/calendar-month.png`,
    });
  });

  test('clicking a date triggers onDateSelect (mouse and keyboard)', async ({
    page,
  }) => {
    const freeCell = page.getByRole('gridcell', {
      name: /No events on 2026-03-15/,
    });
    await freeCell.click();
    let logs = await getLogs(page);
    expect(
      findLog(logs, 'calendar:dateSelect', { date: '2026-03-15' })
    ).toBeTruthy();

    const eventDay = page.getByRole('gridcell', {
      name: /1 event on 2026-03-14/,
    });
    await eventDay.focus();
    await page.keyboard.press('Enter');
    logs = await getLogs(page);
    const dateSelects = logs.filter((e) => e.name === 'calendar:dateSelect');
    expect(dateSelects.length).toBeGreaterThanOrEqual(2);
    expect(dateSelects.map((e) => e.payload.date)).toContain('2026-03-14');
  });

  test('view toggle buttons change the calendar view', async ({ page }) => {
    await expect(page.locator('.rbc-month-view')).toBeVisible();

    const month = page.getByRole('radio', { name: 'Month' });
    await expect(month).toHaveAttribute('aria-checked', 'true');

    await page.getByRole('radio', { name: 'Week' }).click();
    await expect(page.getByRole('radio', { name: 'Week' })).toHaveAttribute(
      'aria-checked',
      'true'
    );
    await expect(month).toHaveAttribute('aria-checked', 'false');
    await expect(page.locator('.rbc-week-view')).toBeVisible();

    await page.getByRole('radio', { name: 'Day' }).click();
    await expect(page.getByRole('radio', { name: 'Day' })).toHaveAttribute(
      'aria-checked',
      'true'
    );
    await expect(page.locator('.rbc-day-view')).toBeVisible();

    await page.getByRole('radio', { name: 'Month' }).click();
    await expect(month).toHaveAttribute('aria-checked', 'true');
    await expect(page.locator('.rbc-month-view')).toBeVisible();

    const logs = await getLogs(page);
    const views = logs
      .filter((e) => e.name === 'calendar:viewChange')
      .map((e) => e.payload.view);
    expect(views).toContain('week');
    expect(views).toContain('day');

    await page.locator('#sec-calendar').screenshot({
      path: `${SHOTS}/calendar-views.png`,
    });
  });

  test('radiogroup supports arrow-key navigation', async ({ page }) => {
    const week = page.getByRole('radio', { name: 'Week' });
    await week.focus();
    await expect(week).toHaveAttribute('aria-checked', 'false');
    await expect(page.getByRole('radio', { name: 'Month' })).toHaveAttribute(
      'tabindex',
      '-1'
    );

    await page.keyboard.press('ArrowRight');
    await expect(week).toHaveAttribute('aria-checked', 'true');
    await expect(page.getByRole('radio', { name: 'Month' })).toHaveAttribute(
      'tabindex',
      '-1'
    );

    const logs = await getLogs(page);
    expect(
      logs.filter((e) => e.name === 'calendar:viewChange')
    ).toHaveLength(1);
  });
});

test.describe('EventCard', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('displays all required fields: thumbnail, title, organizer, date, price, availability', async ({
    page,
  }) => {
    const card = page.locator('[data-testid="eventcard-101"]');
    await expect(card).toBeVisible();
    await expect(card.locator('img')).toBeVisible();
    await expect(card).toContainText('Lagos Jazz Night');
    await expect(card.getByText('Amaka Okonkwo')).toBeVisible();
    await expect(card).toContainText('7:00 PM to 11:00 PM');
    await expect(card).toContainText('from €50.00');

    const greenDot = card.locator('span[aria-hidden="true"]').first();
    await expect(card).toHaveText(/160 tickets remaining/);
    await expect(greenDot).toHaveCSS('background-color', 'rgb(22, 163, 74)');

    const lowCard = page.locator('[data-testid="eventcard-102"]');
    await expect(lowCard).toContainText(/8 tickets remaining/);
    await expect(
      lowCard.locator('span[aria-hidden="true"]').first()
    ).toHaveCSS('background-color', 'rgb(217, 119, 6)');

    const soldOutCard = page.locator('[data-testid="eventcard-103"]');
    await expect(soldOutCard).toHaveText(/Sold Out/);
    await expect(
      soldOutCard.locator('span[aria-hidden="true"]').first()
    ).toHaveCSS('background-color', 'rgb(220, 38, 38)');

    await page.locator('#sec-eventcards').screenshot({
      path: `${SHOTS}/event-cards.png`,
    });
  });

  test('clicking a card fires onClick (mouse and keyboard)', async ({ page }) => {
    const card = page.locator('[data-testid="eventcard-102"]');
    await card.click();
    let logs = await getLogs(page);
    expect(
      logs.filter((e) => e.name === 'eventcard:click' && e.payload.id === 102)
    ).toHaveLength(1);

    await card.focus();
    await page.keyboard.press('Enter');
    logs = await getLogs(page);
    expect(
      logs.filter((e) => e.name === 'eventcard:click' && e.payload.id === 102)
    ).toHaveLength(2);

    await page.locator('#sec-eventcards').screenshot({
      path: `${SHOTS}/event-cards-interaction.png`,
    });
  });

test.describe('FilterSidebar', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('category, price range and location changes fire callbacks', async ({
    page,
  }) => {
    const sidebar = page.locator('[data-testid="section-filter-sidebar"]');

    await sidebar.getByLabelText('Category').selectOption('Tech');
    await sidebar.getByLabelText('Location').fill('Lagos');

    await page.evaluate(() => {
      const el = document.getElementById('price-min-input');
      if (el) {
        el.value = '50';
        el.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });

    const logs = await getLogs(page);
    expect(findLog(logs, 'filter:category', { category: 'Tech' })).toBeTruthy();
    expect(findLog(logs, 'filter:price', { min: 50, max: 200 })).toBeTruthy();
    expect(
      findLog(logs, 'filter:location', { location: 'Lagos' })
    ).toBeTruthy();

    await sidebar.getByRole('button', { name: 'Clear Filters' }).click();
    const afterClear = await getLogs(page);
    expect(
      afterClear.filter((e) => e.name === 'filter:clear')
    ).toHaveLength(1);
  });

  test('form controls are labelled for assistive technologies', async ({
    page,
  }) => {
    const sidebar = page.locator('[data-testid="section-filter-sidebar"]');
    await expect(sidebar.getByLabelText('Category')).toBeVisible();
    await expect(sidebar.getByLabelText('Minimum price')).toBeVisible();
    await expect(sidebar.getByLabelText('Maximum price')).toBeVisible();
    await expect(sidebar.getByLabelText('Location')).toBeVisible();
  });
});

test.describe('SearchBar', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('debounces input and shows autocomplete results', async ({ page }) => {
    const search = page.locator('[data-testid="section-search-bar"]');
    const input = search.getByLabelText('Search events');

    await input.fill('j');
    await page.waitForTimeout(400);
    await expect(search.getByRole('listbox')).not.toBeVisible();

    await input.fill('jas');
    await page.waitForTimeout(400);
    await expect(search.getByRole('listbox')).toBeVisible();
    await expect(
      search.getByRole('option', { name: /Lagos Jazz Night/ })
    ).toBeVisible();

    const logs = await getLogs(page);
    expect(findLog(logs, 'search:query', { query: 'jas' })).toBeTruthy();

    await input.fill('x');
    await page.waitForTimeout(400);
    await expect(search.getByRole('listbox')).not.toBeVisible();
  });

  test('arrow-key navigation and Enter select a result', async ({ page }) => {
    const search = page.locator('[data-testid="section-search-bar"]');
    const input = search.getByLabelText('Search events');

    await input.fill('tech');
    await page.waitForTimeout(400);
    await expect(
      search.getByRole('option', { name: /Tech Summit 2026/ })
    ).toBeVisible();

    await input.press('ArrowDown');
    await input.press('Enter');

    const logs = await getLogs(page);
    expect(
      findLog(logs, 'search:select', { id: 3, title: 'Tech Summit 2026' })
    ).toBeTruthy();
    await expect(input).toHaveValue('Tech Summit 2026');
    await expect(search.getByRole('listbox')).not.toBeVisible();
  });
});

test.describe('DateHeader', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('displays the date in a readable format', async ({ page }) => {
    await expect(page.locator('[data-testid="dateheader-1"]')).toHaveText(
      /Sat, Mar 14, 2026/
    );
    await expect(page.locator('[data-testid="dateheader-2"]')).toHaveText(
      /Tue, Mar 17, 2026/
    );
    await expect(page.locator('[data-testid="dateheader-multi"]')).toHaveText(
      /2 events/
    );
  });
});

test.describe('AvailabilityIndicator', () => {
  test.beforeEach(async ({ page }) => {
    await openGallery(page);
  });

  test('shows the correct color (green/amber/red) and text', async ({
    page,
  }) => {
    const green = page.locator('[data-testid="indicator-available"]');
    await expect(green).toHaveText(/45 tickets remaining/);
    await expect(green).toHaveAttribute('aria-label', '45 tickets remaining');
    await expect(
      green.locator('span[aria-hidden="true"]').first()
    ).toHaveCSS('background-color', 'rgb(22, 163, 74)');

    const amber = page.locator('[data-testid="indicator-low"]');
    await expect(amber).toHaveText(/12 tickets remaining/);
    await expect(amber).toHaveAttribute('aria-label', '12 tickets remaining');
    await expect(
      amber.locator('span[aria-hidden="true"]').first()
    ).toHaveCSS('background-color', 'rgb(217, 119, 6)');

    const red = page.locator('[data-testid="indicator-soldout"]');
    await expect(red).toHaveText(/Sold Out/);
    await expect(red).toHaveAttribute('aria-label', 'Sold Out');
    await expect(
      red.locator('span[aria-hidden="true"]').first()
    ).toHaveCSS('background-color', 'rgb(220, 38, 38)');

    // Missing ticketsRemaining must not render "undefined".
    const undefinedCase = page.locator('[data-testid="indicator-undefined"]');
    await expect(undefinedCase).toHaveText(/0 tickets remaining/);
    await expect(undefinedCase).not.toHaveText(/undefined/);

    await page.locator('#sec-availability').screenshot({
      path: `${SHOTS}/availability-indicators.png`,
    });
  });
});

test.describe('mobile viewport', () => {
  test('sidebar opens as a drawer and calendar/cards stay usable', async ({
    page,
  }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await openGallery(page);

    const sidebar = page.locator('[data-testid="section-filter-sidebar"]');
    await expect(
      sidebar.getByRole('button', { name: 'Filters' })
    ).toBeVisible();
    await sidebar.getByRole('button', { name: 'Filters' }).click();
    await expect(sidebar.locator('#category-select')).toBeVisible();
    await expect(sidebar.locator('#location-input')).toBeVisible();

    await expect(page.getByRole('radio', { name: 'Month' })).toBeVisible();
    await expect(page.locator('.rbc-month-view')).toBeVisible();
    await expect(page.locator('[data-testid="eventcard-101"] img')).toBeVisible();

    await page.locator('#sec-calendar').screenshot({
      path: `${SHOTS}/mobile-calendar.png`,
    });
    await page.screenshot({ path: `${SHOTS}/mobile-full.png`, fullPage: true });
  });

  test('desktop viewport shows the inline sidebar', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await openGallery(page);
    const sidebar = page.locator('[data-testid="section-filter-sidebar"]');
    await expect(sidebar.getByLabelText('Category')).toBeVisible();
    await expect(sidebar.getByRole('button', { name: 'Filters' })).not.toBeVisible();
  });
});
  });