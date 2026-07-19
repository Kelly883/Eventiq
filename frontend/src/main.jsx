import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import * as ReactDOM from 'react-dom'
import * as React from 'react'
import './index.css'
import App from './App.jsx'
import { AuthProvider } from './features/auth/context/AuthContext.jsx'
import { QueryClientProvider } from '@tanstack/react-query'
import { queryClient } from './lib/queryClient'
import { initAnalytics } from './lib/analytics'
import './features/accessibility-localization/i18n/config'

initAnalytics()

// Dev-only accessibility testing - logs WCAG violations to the console
// as the app renders. Dynamic import + the static import.meta.env.DEV
// check lets Vite's build fully exclude this (and axe-core) from the
// production bundle rather than just skip calling it at runtime.
if (import.meta.env.DEV) {
  import('react-axe').then(({ default: axe }) => {
    axe(React, ReactDOM, 1000);
  });
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
