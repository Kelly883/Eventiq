import { useState, useCallback } from 'react';
import { api } from '../../../lib/api';
import { getDeviceToken } from '../services/deviceToken';
import { offlineTicketStore } from '../services/offlineTicketStore';

export interface OfflineSyncOptions {
  lastSyncAt?: string;
  syncVersion?: number;
  perPage?: number;
  cursor?: string;
}

export interface OfflineSyncResult {
  tickets: unknown[];
  lastSyncedAt: string;
  isSyncing: boolean;
  syncError?: string;
  syncVersion?: number;
  nextCursor?: string;
  hasMore?: boolean;
}

export function useOfflineSync(initialOptions: OfflineSyncOptions = {}) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastSyncedAt, setLastSyncedAt] = useState<string | null>(initialOptions.lastSyncAt ?? null);
  const [syncVersion, setSyncVersion] = useState<number>(initialOptions.syncVersion ?? 0);
  const [tickets, setTickets] = useState<unknown[]>([]);
  const [nextCursor, setNextCursor] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState<boolean>(false);

  const sync = useCallback(async (options: OfflineSyncOptions = {}, signal?: AbortSignal, retries = 2) => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.get('/me/tickets/for-offline-sync', {
        params: {
          last_sync_at: options.lastSyncAt ?? lastSyncedAt ?? '',
          sync_version: options.syncVersion ?? syncVersion,
          per_page: options.perPage ?? 50,
          cursor: options.cursor,
        },
        headers: {
          'X-Device-Token': getDeviceToken(),
        },
        timeout: 30000,
        signal,
      });

      const ticketData = response.data?.data ?? [];
      await offlineTicketStore.cacheTickets(ticketData as unknown as Parameters<typeof offlineTicketStore.cacheTickets>[0]);

      const serverLastSyncedAt = new Date().toISOString();
      const nextVersion = (options.syncVersion ?? syncVersion) + 1;
      const pagination = response.data?.pagination ?? {};
      const nextCursorValue = pagination.next_cursor ?? null;
      const hasMoreValue = pagination.has_more ?? false;

      setTickets(ticketData);
      setLastSyncedAt(serverLastSyncedAt);
      setSyncVersion(nextVersion);
      setNextCursor(nextCursorValue);
      setHasMore(hasMoreValue);

      return {
        tickets: ticketData,
        lastSyncedAt: serverLastSyncedAt,
        isSyncing: false,
        syncVersion: nextVersion,
        nextCursor: nextCursorValue,
        hasMore: hasMoreValue,
      } satisfies OfflineSyncResult;
    } catch (e) {
      const axiosError = e as { response?: { status?: number; headers?: Record<string, string> } };
      const status = axiosError?.response?.status;
      const isRateLimited = status === 429;
      const isServerError = typeof status === 'number' && status >= 500 && retries > 0;

      if (isRateLimited && retries > 0) {
        const retryAfter = axiosError?.response?.headers?.['retry-after'];
        const delayMs = retryAfter ? Number(retryAfter) * 1000 : 2000;
        await new Promise((resolve) => setTimeout(resolve, delayMs));
        return sync(options, signal, retries - 1);
      }

      if (isServerError) {
        const delayMs = 1000 * (2 ** (2 - retries));
        await new Promise((resolve) => setTimeout(resolve, delayMs));
        return sync(options, signal, retries - 1);
      }

      const message = e instanceof Error ? e.message : 'Failed to sync offline data';
      setError(message);
      return {
        tickets: [],
        lastSyncedAt: lastSyncedAt ?? new Date().toISOString(),
        isSyncing: false,
        syncError: message,
        syncVersion,
        nextCursor: null,
        hasMore: false,
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
    nextCursor,
    hasMore,
    sync,
  };
}
