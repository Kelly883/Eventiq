import { useState, useCallback } from 'react';
import { api } from '../../../lib/api';
import { getDeviceToken } from '../services/deviceToken';
import { offlineTicketStore } from '../services/offlineTicketStore';

export interface OfflineSyncOptions {
  lastSyncAt?: string;
  syncVersion?: number;
}

export interface OfflineSyncResult {
  tickets: unknown[];
  lastSyncedAt: string;
  isSyncing: boolean;
  syncError?: string;
  syncVersion?: number;
}

export function useOfflineSync(initialOptions: OfflineSyncOptions = {}) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastSyncedAt, setLastSyncedAt] = useState<string | null>(initialOptions.lastSyncAt ?? null);
  const [syncVersion, setSyncVersion] = useState<number>(initialOptions.syncVersion ?? 0);
  const [tickets, setTickets] = useState<unknown[]>([]);

  const sync = useCallback(async (options: OfflineSyncOptions = {}, retries = 2) => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.get('/me/tickets/for-offline-sync', {
        params: {
          last_sync_at: options.lastSyncAt ?? lastSyncedAt ?? '',
          sync_version: options.syncVersion ?? syncVersion,
        },
        headers: {
          'X-Device-Token': getDeviceToken(),
        },
      });

      const ticketData = response.data?.data ?? [];
      await offlineTicketStore.cacheTickets(ticketData as unknown as Parameters<typeof offlineTicketStore.cacheTickets>[0]);

      const serverLastSyncedAt = new Date().toISOString();
      const nextVersion = (options.syncVersion ?? syncVersion) + 1;

      setTickets(ticketData);
      setLastSyncedAt(serverLastSyncedAt);
      setSyncVersion(nextVersion);

      return {
        tickets: ticketData,
        lastSyncedAt: serverLastSyncedAt,
        isSyncing: false,
        syncVersion: nextVersion,
      } satisfies OfflineSyncResult;
    } catch (e) {
      const axiosError = e as { response?: { status?: number } };
      const status = axiosError?.response?.status;
      const isServerError = typeof status === 'number' && status >= 500 && retries > 0;

      if (isServerError) {
        const delayMs = 1000 * (2 ** (2 - retries));
        await new Promise((resolve) => setTimeout(resolve, delayMs));
        return sync(options, retries - 1);
      }

      const message = e instanceof Error ? e.message : 'Failed to sync offline data';
      setError(message);
      return {
        tickets: [],
        lastSyncedAt: lastSyncedAt ?? new Date().toISOString(),
        isSyncing: false,
        syncError: message,
        syncVersion,
      } satisfies OfflineSyncResult;
    } finally {
      setLoading(false);
    }
  }, [lastSyncedAt, syncVersion]);

  return {
    loading,
    error,
    tickets,
    lastSyncedAt,
    syncVersion,
    sync,
  };
}
