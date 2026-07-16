import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage, isSupported } from 'firebase/messaging';

/**
 * Firebase project credentials - these are public client identifiers, safe
 * to expose in frontend code (they're not secrets; Firebase's actual
 * security boundary is server-side rules/App Check, not hiding this
 * config). Get real values from Firebase Console > Project Settings >
 * General > Your apps > Web app.
 */
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

// VAPID key for Web Push - from Firebase Console > Project Settings >
// Cloud Messaging > Web configuration > Web Push certificates.
const VAPID_KEY = import.meta.env.VITE_FIREBASE_VAPID_KEY;

let messagingInstance: ReturnType<typeof getMessaging> | null = null;

function isConfigured(): boolean {
  return Boolean(firebaseConfig.apiKey && firebaseConfig.projectId && firebaseConfig.appId);
}

/**
 * Initializes Firebase and Cloud Messaging. Safe to call multiple times.
 * Returns null (and logs a warning) if config is missing, or if the
 * browser doesn't support the Push API (e.g. Safari < 16, or a
 * non-HTTPS/non-localhost context, which the Push API requires).
 */
export async function initFirebaseMessaging() {
  if (messagingInstance) return messagingInstance;

  if (!isConfigured()) {
    console.warn('[firebase] VITE_FIREBASE_* env vars are not set - push notifications disabled.');
    return null;
  }

  const supported = await isSupported().catch(() => false);
  if (!supported) {
    console.warn('[firebase] Firebase Messaging is not supported in this browser/context.');
    return null;
  }

  const app = initializeApp(firebaseConfig);
  messagingInstance = getMessaging(app);
  return messagingInstance;
}

/**
 * Requests notification permission, then retrieves the FCM token that
 * uniquely identifies this browser/device. Returns null if permission was
 * denied, Firebase isn't configured, or the service worker registration
 * failed - callers should treat null as "push notifications unavailable"
 * rather than an error to surface loudly to the user.
 */
export async function requestNotificationPermissionAndToken(): Promise<string | null> {
  if (typeof Notification === 'undefined') {
    console.warn('[firebase] Notification API not available in this environment.');
    return null;
  }

  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    console.info('[firebase] Notification permission was not granted:', permission);
    return null;
  }

  const messaging = await initFirebaseMessaging();
  if (!messaging) return null;

  try {
    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
    const token = await getToken(messaging, {
      vapidKey: VAPID_KEY,
      serviceWorkerRegistration: registration,
    });
    return token || null;
  } catch (error) {
    console.error('[firebase] Failed to retrieve FCM token:', error);
    return null;
  }
}

/**
 * Registers a handler for messages received while the app is in the
 * foreground. Background messages are handled separately by
 * public/firebase-messaging-sw.js.
 */
export async function onForegroundMessage(callback: (payload: unknown) => void) {
  const messaging = await initFirebaseMessaging();
  if (!messaging) return () => {};

  return onMessage(messaging, callback);
}
