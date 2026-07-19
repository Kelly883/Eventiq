import SHA256 from 'crypto-js/sha256';
import Hex from 'crypto-js/enc-hex';

const DEVICE_TOKEN_STORAGE_KEY = 'eventiqDeviceToken';
let inMemoryDeviceToken: string | null = null;

function getRandomDeviceSeed(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }

  if (typeof crypto !== 'undefined' && 'getRandomValues' in crypto) {
    const values = crypto.getRandomValues(new Uint32Array(4));
    return Array.from(values, (value) => value.toString(16).padStart(8, '0')).join('-');
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
    // Private browsing/storage policy may reject persistence. The in-memory
    // fallback still keeps one stable token for the current page session.
  }
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

export function getDeviceTokenStorageKey(): string {
  return DEVICE_TOKEN_STORAGE_KEY;
}

if (typeof window !== 'undefined') {
  window.EventiqDevice = {
    getDeviceToken,
    storageKey: DEVICE_TOKEN_STORAGE_KEY,
  };
}

declare global {
  interface Window {
    EventiqDevice?: {
      getDeviceToken: () => string;
      storageKey: string;
    };
  }
}
