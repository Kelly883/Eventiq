import { worker } from './browser'

/**
 * Gate for enabling MSW. Intentionally dev-only: the service-worker bundle
 * must never ship to production, so this module is dynamically imported and
 * only under a dev-mode flag. Vite statically removes the import in
 * production builds, which keeps `msw` out of the client bundle entirely.
 *
 * Enable locally with any of:
 *   - `npm run dev:mock`           (runs Vite in `mock` mode)
 *   - an env file entry            VITE_USE_MSW=true
 *   - a query flag                 http://localhost:3000/?msw=1
 */
export async function enableMocking() {
  if (!import.meta.env.DEV) return false

  const envFlag = String(import.meta.env.VITE_USE_MSW ?? '').toLowerCase()
  const modeFlag = import.meta.env.MODE === 'mock'
  const queryFlag =
    typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('msw')

  if (!modeFlag && envFlag !== 'true' && envFlag !== '1' && !queryFlag) return false

  // Deliberately await the worker before any data fetch happens, so the very
  // first request (homepage events/categories) is already intercepted.
  await worker.start({
    onUnhandledRequest: 'bypass',
    quiet: false,
  })

  console.info('[MSW] mock API layer enabled — requests are served from src/mocks')
  return true
}


export { db, seedDatabase } from './db.js'
export { handlers } from './handlers.js'
export { worker } from './browser.js'
