import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'
import { AuthProvider } from './features/auth/context/AuthContext.jsx'
import { QueryClientProvider } from '@tanstack/react-query'
import { queryClient } from './lib/queryClient'
import { initAnalytics } from './lib/analytics'

initAnalytics()

if (import.meta.env.DEV) {
  // Accessibility testing in development.
  // eslint-disable-next-line import/no-extraneous-dependencies
  import('react-axe').then(({ default: Axe }) => {
    // eslint-disable-next-line no-console
    console.debug('[react-axe] enabled (development only)')

    // Wrap nothing; Axe exposes checks via component usage.
    // We'll rely on component-level usage later if/when introduced.
    // For now, log availability.
    void Axe
  }).catch((e) => {
    // eslint-disable-next-line no-console
    console.warn('[react-axe] failed to load', e)
  })
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

