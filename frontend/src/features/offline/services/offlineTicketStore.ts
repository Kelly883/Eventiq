import { openDB, type IDBPDatabase, type IDBPObjectStore } from 'idb';

export type OfflineTicketRecord = {
  id: string | number;
  event_id?: string | number;
  ticket_code?: string;
  updated_at?: string;
  [key: string]: unknown;
};

export type OfflineSyncMetadata = {
  key: string;
  value: unknown;
  updatedAt: string;
};

const DB_NAME = 'eventiq-offline-sync-db';
const DB_VERSION = 2;
const TICKETS_STORE = 'tickets';
const METADATA_STORE = 'syncMetadata';
const TICKET_CODE_INDEX = 'by_ticket_code';
const EVENT_ID_INDEX = 'by_event_id';
const UPDATED_AT_INDEX = 'by_updated_at';

let dbPromise: Promise<IDBPDatabase> | null = null;

function getTicketKey(ticket: OfflineTicketRecord): string {
  return String(ticket.id ?? ticket.ticket_code);
}

function ensureTicketIndexes(store: IDBPObjectStore<unknown, ArrayLike<string>, string, 'versionchange'>): void {
  if (!store.indexNames.contains(TICKET_CODE_INDEX)) {
    store.createIndex(TICKET_CODE_INDEX, 'ticket_code', { unique: false });
  }

  if (!store.indexNames.contains(EVENT_ID_INDEX)) {
    store.createIndex(EVENT_ID_INDEX, 'event_id', { unique: false });
  }

  if (!store.indexNames.contains(UPDATED_AT_INDEX)) {
    store.createIndex(UPDATED_AT_INDEX, 'updated_at', { unique: false });
  }
}

function getDB(): Promise<IDBPDatabase> {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('IndexedDB is only available in browser environments.'));
  }

  if (!dbPromise) {
    dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db, oldVersion, _newVersion, transaction) {
        let ticketStore: IDBPObjectStore<unknown, ArrayLike<string>, string, 'versionchange'>;

        if (!db.objectStoreNames.contains(TICKETS_STORE)) {
          ticketStore = db.createObjectStore(TICKETS_STORE, { keyPath: 'id' });
        } else {
          ticketStore = transaction.objectStore(TICKETS_STORE);
        }

        if (oldVersion < 2) {
          ensureTicketIndexes(ticketStore);
        }

        if (!db.objectStoreNames.contains(METADATA_STORE)) {
          db.createObjectStore(METADATA_STORE, { keyPath: 'key' });
        }
      },
    });
  }

  return dbPromise;
}

export const offlineTicketStore = {
  async cacheTickets(tickets: OfflineTicketRecord[]): Promise<void> {
    const db = await getDB();
    const tx = db.transaction(TICKETS_STORE, 'readwrite');

    await Promise.all(
      tickets.map((ticket) => tx.store.put({ ...ticket, id: getTicketKey(ticket) })),
    );
    await tx.done;
  },

  async getTickets(): Promise<OfflineTicketRecord[]> {
    const db = await getDB();
    return db.getAll(TICKETS_STORE);
  },

  async getTicketByCode(ticketCode: string): Promise<OfflineTicketRecord | undefined> {
    const db = await getDB();
    return db.getFromIndex(TICKETS_STORE, TICKET_CODE_INDEX, ticketCode);
  },

  async getTicketsByEventId(eventId: string | number): Promise<OfflineTicketRecord[]> {
    const db = await getDB();
    return db.getAllFromIndex(TICKETS_STORE, EVENT_ID_INDEX, eventId);
  },

  async getTicketsUpdatedSince(updatedAt: string): Promise<OfflineTicketRecord[]> {
    const db = await getDB();
    return db.getAllFromIndex(TICKETS_STORE, UPDATED_AT_INDEX, IDBKeyRange.lowerBound(updatedAt, true));
  },

  async clearTickets(): Promise<void> {
    const db = await getDB();
    await db.clear(TICKETS_STORE);
  },

  async setMetadata(key: string, value: unknown): Promise<void> {
    const db = await getDB();
    await db.put(METADATA_STORE, {
      key,
      value,
      updatedAt: new Date().toISOString(),
    } satisfies OfflineSyncMetadata);
  },

  async getMetadata<TValue = unknown>(key: string): Promise<TValue | null> {
    const db = await getDB();
    const record = (await db.get(METADATA_STORE, key)) as OfflineSyncMetadata | undefined;
    return (record?.value as TValue | undefined) ?? null;
  },
};
