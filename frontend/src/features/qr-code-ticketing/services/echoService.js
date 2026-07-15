import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Attach Pusher to window object for Laravel Echo compatibility
if (typeof window !== 'undefined') {
  window.Pusher = Pusher;
}

let echoInstance = null;

/**
 * Get or initialize the Laravel Echo instance for real-time event broadcasting.
 */
export const getEchoInstance = () => {
  if (typeof window === 'undefined') return null;

  if (!echoInstance) {
    try {
      const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || 'eventiq_pusher_key';
      const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';
      const wsHost = import.meta.env.VITE_PUSHER_HOST || undefined;
      const wsPort = import.meta.env.VITE_PUSHER_PORT || undefined;

      echoInstance = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: cluster,
        forceTLS: true,
        wsHost: wsHost,
        wsPort: wsPort ? parseInt(wsPort, 10) : undefined,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
      });
      console.log('Laravel Echo client initialized successfully.');
    } catch (error) {
      console.error('Failed to initialize Laravel Echo client:', error);
    }
  }

  return echoInstance;
};
