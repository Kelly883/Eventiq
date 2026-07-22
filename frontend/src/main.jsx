import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'
import { AuthProvider } from './features/auth/context/AuthContext.jsx'
import { QueryClientProvider } from '@tanstack/react-query'
import { queryClient } from './lib/queryClient'
import { initAnalytics } from './lib/analytics'
import { initAccessibilityLocalizationI18n } from './features/accessibility-localization/i18n/config'

initAnalytics()

async function bootstrap() {
  try {
    await initAccessibilityLocalizationI18n()
  } catch (e) {
    console.warn('[i18n] failed to initialize', e)
  }

  if (import.meta.env.DEV) {
    // Accessibility testing in development — axe must be initialised before mount.
    try {
      const [{ default: Axe }, ReactModule, ReactDOMModule] = await Promise.all([
        import('react-axe'),
        import('react'),
        import('react-dom'),
      ])
      Axe(ReactModule.default ?? ReactModule, ReactDOMModule.default ?? ReactDOMModule, 1000)
      console.debug('[react-axe] enabled (development only)')
    } catch (e) {
      console.warn('[react-axe] failed to load', e)
    }
  }

  createRoot(document.getElementById('root')).render(
    <StrictMode>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <App />
        </AuthProvider>
      </QueryClientProvider>
    </StrictMode>,
  )
}

bootstrap()

