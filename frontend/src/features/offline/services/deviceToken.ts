import SHA256 from 'crypto-js/sha256';
import Hex from 'crypto-js/enc-hex';
import { openDB, type IDBPDatabase } from 'idb';

const DEVICE_TOKEN_STORAGE_KEY = 'eventiqDeviceToken';
const DEVICE_TOKEN_IDB_DB = 'eventiq-offline-sync-db';
const DEVICE_TOKEN_IDB_STORE = 'syncMetadata';
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

let idbDevicePromise: Promise<string | null> | null = null;

async function readIndexedDbDeviceToken(): Promise<string | null> {
  if (typeof window === 'undefined') return null;
  if (!idbDevicePromise) {
    idbDevicePromise = (async (): Promise<string | null> => {
      try {
        const db = await openDB(DEVICE_TOKEN_IDB_DB, 2, {
          upgrade(db) {
            if (!db.objectStoreNames.contains(DEVICE_TOKEN_IDB_STORE)) {
              db.createObjectStore(DEVICE_TOKEN_IDB_STORE, { keyPath: 'key' });
            }
          },
        });
        const record = (await db.get(DEVICE_TOKEN_IDB_STORE, DEVICE_TOKEN_IDB_KEY)) as
          | { value?: string }
          | undefined;
        return record?.value ?? null;
      } catch {
        return null;
      }
    })();
  }
  return idbDevicePromise;
}

function writeStoredDeviceToken(token: string): void {
  try {
    localStorage.setItem(DEVICE_TOKEN_STORAGE_KEY, token);
  } catch {
    // Private browsing/storage policy may reject persistence.
  }

  writeIndexedDbDeviceToken(token).catch(() => {
    // best-effort persistence
  });
}

async function writeIndexedDbDeviceToken(token: string): Promise<void> {
  if (typeof window === 'undefined') return;
  try {
    const db = await openDB(DEVICE_TOKEN_IDB_DB, 2, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(DEVICE_TOKEN_IDB_STORE)) {
          db.createObjectStore(DEVICE_TOKEN_IDB_STORE, { keyPath: 'key' });
        }
      },
    });
    await db.put(DEVICE_TOKEN_IDB_STORE, {
      key: DEVICE_TOKEN_IDB_KEY,
      value: token,
      updatedAt: new Date().toISOString(),
    });
  } catch {
    // ignore persistence failures
  }
}

export async function clearStoredDeviceToken(): Promise<void> {
  try {
    localStorage.removeItem(DEVICE_TOKEN_STORAGE_KEY);
  } catch {
    // ignore
  }

  if (typeof window !== 'undefined') {
    try {
      const db = await openDB(DEVICE_TOKEN_IDB_DB, 2, {
        upgrade(db) {
          if (!db.objectStoreNames.contains(DEVICE_TOKEN_IDB_STORE)) {
            db.createObjectStore(DEVICE_TOKEN_IDB_STORE, { keyPath: 'key' });
          }
        },
      });
      await db.delete(DEVICE_TOKEN_IDB_STORE, DEVICE_TOKEN_IDB_KEY);
    } catch {
      // ignore
    }
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

export function getDeviceTokenStorageKey(): string {
  return DEVICE_TOKEN_STORAGE_KEY;
}

if (typeof window !== 'undefined') {
  window.EventiqDevice = {
    getDeviceToken,
    storageKey: DEVICE_TOKEN_STORAGE_KEY,
    clearToken: clearStoredDeviceToken,
  };
}

declare global {
  interface Window {
    EventiqDevice?: {
      getDeviceToken: () => string;
      storageKey: string;
      clearToken: () => Promise<void>;
    };
  }
}
