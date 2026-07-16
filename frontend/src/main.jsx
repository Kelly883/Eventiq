import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'
import { AuthProvider } from './features/auth/context/AuthContext.jsx'
import { QueryClientProvider } from '@tanstack/react-query'
import { queryClient } from './lib/queryClient'
import { initAnalytics } from './lib/analytics'
import { requestNotificationPermissionAndToken } from './config/firebase'
import { api } from './lib/api'

initAnalytics()

// Push notification opt-in. Only attempted if the user already has an
// auth session (device-token registration requires auth:sanctum on the
// backend) - skips silently on first load before login rather than
// prompting for notification permission before the user has even signed
// in, which would be a confusing/premature UX moment.
if (typeof window !== 'undefined' && localStorage.getItem('authToken')) {
  requestNotificationPermissionAndToken()
    .then((token) => {
      if (!token) return;
      return api.post('/device-tokens', { fcm_token: token, platform: 'web' });
    })
    .catch((err) => {
      console.warn('[firebase] Device token registration failed:', err);
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
