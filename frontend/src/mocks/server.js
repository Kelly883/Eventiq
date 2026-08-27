import { setupServer } from 'msw/node'
import { handlers } from './handlers.js'

/**
 * Node-side MSW instance for unit/integration tests (vitest/jsdom, etc.).
 * Start it in a shared setup file with server.listen({ onUnhandledRequest: 'warn' })
 * and reset between tests with server.resetHandlers().
 */
export const server = setupServer(...handlers)
