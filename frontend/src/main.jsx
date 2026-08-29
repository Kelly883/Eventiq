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
  // Start MSW (dev + flag only) before the first render so the homepage's
  // initial /events and /categories requests are intercepted from the start.
  // Dynamic import keeps msw out of production bundles entirely.
  if (import.meta.env.DEV) {
    try {
      const { enableMocking } = await import('./mocks')
      await enableMocking()
    } catch (e) {
      console.warn('[msw] failed to enable mock layer', e)
    }
  }

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

bootstrap().catch((e) => {
  // Bootstrap-level failure: an eager import threw or the first render
  // crashed outside the error boundary, so React never mounted and the
  // user would see a permanent blank white page. Surface the REAL error
  // visibly (never silently blank) and keep it in the console.
  console.error('[bootstrap] Eventiq failed to start:', e)

  const root = document.getElementById('root')
  if (root && root.childElementCount === 0) {
    const message = e instanceof Error ? e.message : String(e)
    const stack = e instanceof Error && e.stack ? e.stack : ''
    root.innerHTML = `
      <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:system-ui,sans-serif;background:#f8fafc;color:#0f172a">
        <div style="max-width:560px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
          <div style="font-size:32px;margin-bottom:8px">&#9888;&#65039;</div>
          <h1 style="font-size:20px;font-weight:700;margin:0 0 8px">Eventiq failed to start</h1>
          <p style="font-size:14px;color:#475569;margin:0 0 16px">The application could not initialize. The error below has been logged to the console for diagnosis.</p>
          <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;overflow:auto;white-space:pre-wrap;word-break:break-word;margin:0 0 16px">${message}${stack ? '\n\n' + String(stack).slice(0, 1500) : ''}</pre>
          <button onclick="window.location.reload()" style="background:#4f46e5;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer">Reload</button>
        </div>
      </div>`
  }
})