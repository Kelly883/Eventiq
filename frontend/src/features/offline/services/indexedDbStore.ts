import { openDB, IDBPDatabase } from 'idb';
import { QueuedCheckIn } from './offlineSyncStore';

const DB_NAME = 'eventiq-offline-db';
const STORE_NAME = 'checkins';
const DB_VERSION = 1;

let dbPromise: Promise<IDBPDatabase> | null = null;

const getDB = (): Promise<IDBPDatabase> => {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('IndexedDB is only available in browser environment.'));
  }

  if (!dbPromise) {
    dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id' });
        }
      },
    });
  }
  return dbPromise;
};

export const indexedDbStore = {
  /**
   * Save a single queued check-in to IndexedDB.
   */
  async saveCheckIn(checkIn: QueuedCheckIn): Promise<void> {
    const db = await getDB();
    await db.put(STORE_NAME, checkIn);
  },

  /**
   * Retrieve all queued check-ins from IndexedDB.
   */
  async getAllCheckIns(): Promise<QueuedCheckIn[]> {
    try {
      const db = await getDB();
      return await db.getAll(STORE_NAME);
    } catch (e) {
      console.warn('Failed to read check-ins from IndexedDB:', e);
      return [];
    }
  },

  /**
   * Delete a checked-in item by ID once it is synchronized or dismissed.
   */
  async deleteCheckIn(id: string): Promise<void> {
    const db = await getDB();
    await db.delete(STORE_NAME, id);
  },

  /**
   * Clear all items in the store.
   */
  async clearAll(): Promise<void> {
    const db = await getDB();
    await db.clear(STORE_NAME);
  },
};
