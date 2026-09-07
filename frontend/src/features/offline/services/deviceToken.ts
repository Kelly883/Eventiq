import SHA256 from 'crypto-js/sha256';
import Hex from 'crypto-js/enc-hex';
import { offlineTicketStore } from './offlineTicketStore';

const DEVICE_TOKEN_STORAGE_KEY = 'eventiqDeviceToken';
const DEVICE_TOKEN_IDB_KEY = 'deviceToken';
let inMemoryDeviceToken: string | null = null;

function getRandomDeviceSeed(): string {
  const cryptoObj = typeof window !== 'undefined' ? window.crypto : undefined;
  if (cryptoObj && 'randomUUID' in cryptoObj) {
    return cryptoObj.randomUUID();
  }

  if (cryptoObj && 'getRandomValues' in cryptoObj) {
    const values = (cryptoObj as Crypto).getRandomValues(new Uint32Array(4));
    return Array.from(values, (value: number) => value.toString(16).padStart(8, '0')).join('-');
  }

  return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

function readStoredDeviceToken(): string | null {
  try {
    return localStorage.getItem(DEVICE_TOKEN_STORAGE_KEY);
  } catch {
    return null;
  }
}

function writeStoredDeviceToken(token: string): void {
  try {
    localStorage.setItem(DEVICE_TOKEN_STORAGE_KEY, token);
  } catch {
    // Private browsing/storage policy may reject persistence.
  }

  // Best-effort IDB backup. Persistence goes through offlineTicketStore so
  // there is exactly ONE owner of the 'eventiq-offline-sync-db' schema.
  offlineTicketStore.setMetadata(DEVICE_TOKEN_IDB_KEY, token).catch(() => {
    // best-effort persistence
  });
}

/**
 * Mint a genuinely fresh device token, ignoring the in-memory cache. Use after
 * the server has rotated/deleted the previous token: getDeviceToken() alone
 * would keep returning the cached (now invalid) identity from memory.
 */
export function forceNewDeviceToken(): string {
  inMemoryDeviceToken = null;
  try {
    localStorage.removeItem(DEVICE_TOKEN_STORAGE_KEY);
  } catch {
    // ignore
  }
  return getDeviceToken();
}

export async function clearStoredDeviceToken(): Promise<void> {
  try {
    localStorage.removeItem(DEVICE_TOKEN_STORAGE_KEY);
  } catch {
    // ignore
  }

  try {
    await offlineTicketStore.deleteMetadata(DEVICE_TOKEN_IDB_KEY);
  } catch {
    // ignore
  }

  inMemoryDeviceToken = null;
}

export function getDeviceToken(): string {
  const storedToken = readStoredDeviceToken();
  if (storedToken) {
    inMemoryDeviceToken = storedToken;
    return storedToken;
  }

  if (inMemoryDeviceToken) {
    return inMemoryDeviceToken;
  }

  // The token is a pseudonymous device identifier, not an authentication
  // secret. A random per-install seed is hashed before storage/transmission so
  // the backend receives a fixed-length opaque identifier.
  const token = SHA256(getRandomDeviceSeed()).toString(Hex);
  inMemoryDeviceToken = token;
  writeStoredDeviceToken(token);
  return token;
}

/**
 * Recover the device token persisted to IndexedDB when localStorage was
 * evicted or cleared. Keeps the same device association server-side (device
 * rows, offline_enabled flag, queued operations) instead of silently minting a
 * brand-new pseudonymous identity on the next request.
 */
export async function restoreDeviceToken(): Promise<string | null> {
  const stored = readStoredDeviceToken();
  if (stored) {
    return stored;
  }

  // Both in-memory and IndexedDB can hold a valid token. Whenever one exists
  // but localStorage does not (eviction/clearing), re-persist it so the next
  // session does not lose the identity again.
  let candidate = inMemoryDeviceToken;
  if (!candidate) {
    try {
      candidate = await offlineTicketStore.getMetadata<string>(DEVICE_TOKEN_IDB_KEY);
    } catch {
      // IDB unavailable — a fresh token will be generated on first use.
    }
  }

  if (candidate && typeof candidate === 'string' && /^[a-f0-9]{64}$/i.test(candidate)) {
    writeStoredDeviceToken(candidate);
    return candidate;
  }

  return null;
}

export function getDeviceTokenStorageKey(): string {
  return DEVICE_TOKEN_STORAGE_KEY;
}

if (typeof window !== 'undefined') {
  // Best-effort early restore: the request interceptor reads the token
  // synchronously, so recovering the persisted identity before the first
  // network call matters. If localStorage is intact this is a no-op.
  restoreDeviceToken();

  window.EventiqDevice = {
    getDeviceToken,
    forceNewDeviceToken,
    storageKey: DEVICE_TOKEN_STORAGE_KEY,
    clearToken: clearStoredDeviceToken,
    restoreToken: restoreDeviceToken,
  };
}

declare global {
  interface Window {
    EventiqDevice?: {
      getDeviceToken: () => string;
      forceNewDeviceToken: () => string;
      storageKey: string;
      clearToken: () => Promise<void>;
      restoreToken: () => Promise<string | null>;
    };
  }
}
