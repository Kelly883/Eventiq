// Firebase Cloud Messaging service worker - handles push notifications
// received while the app is closed or backgrounded. This file must live
// at the site root (public/firebase-messaging-sw.js -> served at
// /firebase-messaging-sw.js) for its scope to cover the whole origin.
//
// Service workers can't use Vite env vars or ES module imports the way
// the rest of the app does - Firebase's compat build (loaded via
// importScripts) is the standard way to configure this file. Values
// below must be filled in manually to match your VITE_FIREBASE_* env
// vars (see src/config/firebase.ts) since this file isn't processed by
// Vite's build step.

importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: 'REPLACE_WITH_FIREBASE_API_KEY',
  authDomain: 'REPLACE_WITH_FIREBASE_AUTH_DOMAIN',
  projectId: 'REPLACE_WITH_FIREBASE_PROJECT_ID',
  storageBucket: 'REPLACE_WITH_FIREBASE_STORAGE_BUCKET',
  messagingSenderId: 'REPLACE_WITH_FIREBASE_MESSAGING_SENDER_ID',
  appId: 'REPLACE_WITH_FIREBASE_APP_ID',
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  const title = payload.notification?.title || 'EventIQ';
  const options = {
    body: payload.notification?.body,
    icon: '/icon-192.png',
    data: payload.data,
  };

  self.registration.showNotification(title, options);
});

// Focus/open the app when a notification is clicked.
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then((clients) => {
      if (clients.length > 0) {
        return clients[0].focus();
      }
      return self.clients.openWindow('/');
    })
  );
});
