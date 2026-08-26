import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import './index.css'
import './env'
import App from './App.jsx'
import { AuthProvider } from './features/auth/context/AuthContext.jsx'
import { QueryClientProvider } from '@tanstack/react-query'
import { queryClient } from './lib/queryClient'
import GlobalErrorBoundary from './features/common/components/ErrorBoundary'
import { initAnalytics } from './lib/analytics'
import { initAccessibilityLocalizationI18n } from './features/accessibility-localization/i18n/config'
import { runBootDiagnostics } from './lib/bootDiagnostics'

async function bootstrap() {
  // Create the root first so the UI appears immediately.
  const root = createRoot(document.getElementById('root'))

  // Render the application shell wrapped in the global error boundary.
  root.render(
    <GlobalErrorBoundary>
      <StrictMode>
        <BrowserRouter>
          <QueryClientProvider client={queryClient}>
            <AuthProvider>
              <App />
            </AuthProvider>
          </QueryClientProvider>
        </BrowserRouter>
      </StrictMode>
    </GlobalErrorBoundary>,
  )

  // Run boot diagnostics after the shell is on screen.
  // This must not block the initial paint, so we use setTimeout(0) to
  // schedule it after the current render cycle.
  setTimeout(() => {
    const report = runBootDiagnostics()
    // Optionally log in development; in production the report is stored
    // in window.__eiBootReport__ for DevTools/Error Boundary consumption.
    if (import.meta.env.DEV) {
      console.debug('[boot diagnostics]', report)
    }
  }, 0)

  // --- Optional initialisation that must not prevent core rendering ---
  try {
    await initAccessibilityLocalizationI18n()
  } catch (e) {
    console.warn('[i18n] failed to initialise after mount', e)
  }

  try {
    initAnalytics()
  } catch (e) {
    console.warn('[analytics] failed to initialise after mount', e)
  }

  if (import.meta.env.DEV) {
    try {
      const [{ default: Axe }, ReactModule, ReactDOMModule] = await Promise.all([
        import('react-axe'),
        import('react'),
        import('react-dom'),
      ])
      Axe(ReactModule.default ?? ReactModule, ReactDOMModule.default ?? ReactDOMModule, 1000)
      console.debug('[react-axe] enabled (development only)')
    } catch (e) {
      console.warn('[react-axe] failed to load after mount', e)
    }
  }
}

bootstrap()