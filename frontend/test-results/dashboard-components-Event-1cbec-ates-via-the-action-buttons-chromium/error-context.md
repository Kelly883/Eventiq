# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: dashboard-components.spec.ts >> EventDetailWidget >> navigates via the action buttons
- Location: tests/dashboard-components.spec.ts:476:3

# Error details

```
Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:3000/dashboard-components
Call log:
  - navigating to "http://localhost:3000/dashboard-components", waiting until "load"

```

# Test source

```ts
  181 |       price: 5000,
  182 |       totalAmount: 20000,
  183 |     },
  184 |     ...ACTIVITY_PAYLOAD.activities,
  185 |   ],
  186 | };
  187 | 
  188 | const EMPTY_ACTIVITY_PAYLOAD = { success: true, enabled: true, activities: [] };
  189 | 
  190 | /* -------------------------------------------------------------------------- */
  191 | /*                                    Helpers                                  */
  192 | /* -------------------------------------------------------------------------- */
  193 | 
  194 | type StubOptions = {
  195 |   activityPayload?: any;
  196 |   emptyMetrics?: boolean;
  197 |   emptyEvents?: boolean;
  198 |   emptyDetail?: boolean;
  199 |   activityError?: boolean;
  200 | };
  201 | 
  202 | async function stubApi(page: Page, options: StubOptions = {}) {
  203 |   const activityCalls = { count: 0 };
  204 | 
  205 |   await page.route(/organizer\/dashboard\/metrics/, async (route) => {
  206 |     if (route.request().method() === 'OPTIONS') {
  207 |       return route.fulfill({ status: 204, headers: CORS });
  208 |     }
  209 |     await new Promise((r) => setTimeout(r, 80));
  210 |     return route.fulfill({
  211 |       status: 200,
  212 |       contentType: 'application/json',
  213 |       headers: CORS,
  214 |       body: JSON.stringify(options.emptyMetrics ? { success: true, metrics: {}, trends: {} } : METRICS_PAYLOAD),
  215 |     });
  216 |   });
  217 | 
  218 |   await page.route(/organizer\/dashboard\/events\/[^/]+/, async (route) => {
  219 |     if (route.request().method() === 'OPTIONS') {
  220 |       return route.fulfill({ status: 204, headers: CORS });
  221 |     }
  222 |     await new Promise((r) => setTimeout(r, 80));
  223 |     if (options.emptyDetail) {
  224 |       return route.fulfill({
  225 |         status: 404,
  226 |         contentType: 'application/json',
  227 |         headers: CORS,
  228 |         body: JSON.stringify({ message: 'Event not found' }),
  229 |       });
  230 |     }
  231 |     return route.fulfill({
  232 |       status: 200,
  233 |       contentType: 'application/json',
  234 |       headers: CORS,
  235 |       body: JSON.stringify(EVENT_DETAIL_PAYLOAD),
  236 |     });
  237 |   });
  238 | 
  239 |   await page.route(/organizer\/dashboard\/events(\?|$)/, async (route) => {
  240 |     if (route.request().method() === 'OPTIONS') {
  241 |       return route.fulfill({ status: 204, headers: CORS });
  242 |     }
  243 |     await new Promise((r) => setTimeout(r, 80));
  244 |     return route.fulfill({
  245 |       status: 200,
  246 |       contentType: 'application/json',
  247 |       headers: CORS,
  248 |       body: JSON.stringify(options.emptyEvents ? { success: true, events: [] } : EVENTS_PAYLOAD),
  249 |     });
  250 |   });
  251 | 
  252 |   await page.route(/organizer\/dashboard\/activity-feed/, async (route) => {
  253 |     if (route.request().method() === 'OPTIONS') {
  254 |       return route.fulfill({ status: 204, headers: CORS });
  255 |     }
  256 |     await new Promise((r) => setTimeout(r, 80));
  257 |     if (options.activityError) {
  258 |       return route.fulfill({
  259 |         status: 500,
  260 |         contentType: 'application/json',
  261 |         headers: CORS,
  262 |         body: JSON.stringify({ message: 'boom' }),
  263 |       });
  264 |     }
  265 |     activityCalls.count += 1;
  266 |     const payload =
  267 |       options.activityPayload ??
  268 |       (activityCalls.count > 1 ? ACTIVITY_POLLED_PAYLOAD : ACTIVITY_PAYLOAD);
  269 |     return route.fulfill({
  270 |       status: 200,
  271 |       contentType: 'application/json',
  272 |       headers: CORS,
  273 |       body: JSON.stringify(payload),
  274 |     });
  275 |   });
  276 | 
  277 |   return activityCalls;
  278 | }
  279 | 
  280 | async function openGallery(page: Page) {
> 281 |   await page.goto(GALLERY);
      |              ^ Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:3000/dashboard-components
  282 |   await page.waitForSelector('[data-testid="dashboard-gallery"]', { timeout: 20000 });
  283 | }
  284 | 
  285 | function logEntries(page: Page): Promise<{ name: string; payload: any }[]> {
  286 |   return page.evaluate(() => window.__dashboardGalleryLog ?? []);
  287 | }
  288 | 
  289 | async function logNames(page: Page): Promise<string[]> {
  290 |   return (await logEntries(page)).map((e) => e.name);
  291 | }
  292 | 
  293 | /* ========================================================================== *
  294 |  * MetricCard
  295 |  * ========================================================================== */
  296 | 
  297 | test.describe('MetricCard', () => {
  298 |   test.beforeEach(async ({ page }) => {
  299 |     await stubApi(page);
  300 |     await openGallery(page);
  301 |   });
  302 | 
  303 |   test('displays label, value and trend indicator with the right colours', async ({ page }) => {
  304 |     const card = page.locator('[data-testid="metric-total-revenue"]');
  305 | 
  306 |     await expect(card.getByText('Total Revenue', { exact: true })).toBeVisible();
  307 |     await expect(card.getByText('₦4,250,000')).toBeVisible();
  308 | 
  309 |     const trend = card.locator('[aria-live="polite"]');
  310 |     await expect(trend).toHaveText(/\+12\.5%/);
  311 |     await expect(trend).toHaveAttribute('aria-label', /Trending up by \+12\.5%/);
  312 |     await expect(trend).toHaveClass(/text-emerald-700/);
  313 |     await expect(trend).toHaveClass(/bg-emerald-50/);
  314 | 
  315 |     await card.screenshot({ path: `${SHOTS}/metriccard-desktop.png` });
  316 |   });
  317 | 
  318 |   test('formats non-currency metrics and colours down/neutral trends', async ({ page }) => {
  319 |     const events = page.locator('[data-testid="metric-active-events"]');
  320 |     await expect(events.getByText('24', { exact: true })).toBeVisible();
  321 |     await expect(events.locator('[aria-live="polite"]')).toHaveClass(/text-slate-500/);
  322 | 
  323 |     const tickets = page.locator('[data-testid="metric-tickets-sold"]');
  324 |     await expect(tickets.getByText('1,204')).toBeVisible();
  325 |     await expect(tickets.locator('[aria-live="polite"]')).toHaveText(/\+12\.5%/);
  326 |     await expect(tickets.locator('[aria-live="polite"]')).toHaveClass(/text-emerald-700/);
  327 |   });
  328 | 
  329 |   test('renders the loading skeleton instead of a value while data loads', async ({ page }) => {
  330 |     const loading = page.locator('[data-testid="metric-loading"]');
  331 | 
  332 |     // The card switches to a skeleton: pulsing bars for label, value, trend (3 total), no value, aria-busy.
  333 |     await expect(loading.locator('.animate-pulse')).toHaveCount(3);
  334 |     await expect(loading.locator('[aria-busy="true"]')).toHaveCount(1);
  335 |     await expect(loading.getByText('₦4,250,000')).toHaveCount(0);
  336 |     await expect(loading.locator('[aria-live="polite"]')).toHaveCount(0);
  337 | 
  338 |     await loading.screenshot({ path: `${SHOTS}/metriccard-loading.png` });
  339 |   });
  340 | 
  341 |   test('falls back to a placeholder when a metric has no value', async ({ page }) => {
  342 |     const empty = page.locator('[data-testid="metric-empty"]');
  343 |     await expect(empty.getByText('—')).toBeVisible();
  344 |     await expect(empty.locator('[aria-live="polite"]')).toHaveText(/0%/);
  345 |   });
  346 | });
  347 | 
  348 | /* ========================================================================== *
  349 |  * EventCard
  350 |  * ========================================================================== */
  351 | 
  352 | test.describe('EventCard', () => {
  353 |   test.beforeEach(async ({ page }) => {
  354 |     await stubApi(page);
  355 |     await openGallery(page);
  356 |   });
  357 | 
  358 |   test('shows thumbnail, title, date, status badge, progress, revenue and attendees', async ({
  359 |     page,
  360 |   }) => {
  361 |     const card = page.locator('[data-testid="eventcard-main"]');
  362 | 
  363 |     await expect(card.locator('img')).toBeVisible();
  364 |     await expect(card.locator('h3')).toHaveText('Lagos Jazz Festival 2026');
  365 |     await expect(card.getByText('Eko Convention Centre')).toBeVisible();
  366 | 
  367 |     // Status badge
  368 |     await expect(card.getByText('Active', { exact: true })).toBeVisible();
  369 | 
  370 |     // Progress bar
  371 |     const progress = card.locator('[role="progressbar"]');
  372 |     await expect(progress).toHaveAttribute('aria-valuenow', '81');
  373 | 
  374 |     // Revenue + attendees
  375 |     await expect(card.getByText('₦4,250,000')).toBeVisible();
  376 |     await expect(card.getByText('812', { exact: true })).toBeVisible();
  377 | 
  378 |     await card.screenshot({ path: `${SHOTS}/eventcard-desktop.png` });
  379 |   });
  380 | 
  381 |   test('triggers the correct callbacks from the card actions', async ({ page }) => {
```