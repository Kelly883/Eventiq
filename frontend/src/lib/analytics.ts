import mixpanel from 'mixpanel-browser';

const token = import.meta.env.VITE_MIXPANEL_TOKEN;
let initialized = false;

/**
 * Initialize Mixpanel once, at app startup. Safe to call multiple times.
 * No-ops (with a console warning) if no token is configured, so local/dev
 * environments without a token don't throw.
 */
export function initAnalytics(): void {
  if (initialized) return;

  if (!token) {
    console.warn(
      '[analytics] VITE_MIXPANEL_TOKEN is not set — Mixpanel tracking is disabled.'
    );
    return;
  }

  mixpanel.init(token, {
    track_pageview: true,
    persistence: 'localStorage',
    debug: import.meta.env.DEV,
  });

  initialized = true;
}

/**
 * Track an event. Silently no-ops if Mixpanel was never initialized
 * (e.g. missing token), so call sites don't need to guard every call.
 */
export function track(eventName: string, properties?: Record<string, unknown>): void {
  if (!initialized) return;
  mixpanel.track(eventName, properties);
}

/**
 * Specifically tracks a ticket purchase event with metadata.
 */
export function trackTicketPurchased(tierName: string, price: number, timestamp: string | Date = new Date()): void {
  track('ticket_purchased', {
    tier: tierName,
    price: price,
    timestamp: typeof timestamp === 'string' ? timestamp : timestamp.toISOString(),
  });
}

export function identify(userId: string): void {
  if (!initialized) return;
  mixpanel.identify(userId);
}

export function setUserProperties(properties: Record<string, unknown>): void {
  if (!initialized) return;
  mixpanel.people.set(properties);
}

export { mixpanel };
