import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { onlineManager } from '@tanstack/react-query';
import axios from 'axios';
import { indexedDbStore } from './indexedDbStore';

export interface QueuedCheckIn {
  id: string; // client-side generated UUID or timestamp
  ticketCode: string;
  eventId: number | string;
  scannedAt: string;
  status: 'pending' | 'syncing' | 'synced' | 'failed';
  error?: string;
  retryCount: number;
}

interface OfflineSyncState {
  queue: QueuedCheckIn[];
  history: QueuedCheckIn[];
  isOnline: boolean;
  isSyncing: boolean;
  
  // Actions
  setOnlineStatus: (isOnline: boolean) => void;
  enqueueScan: (ticketCode: string, eventId: number | string) => Promise<void>;
  syncQueue: () => Promise<void>;
  clearSyncedHistory: () => void;
  removeFailedScan: (id: string) => Promise<void>;
  loadOfflineQueue: () => Promise<void>;
}

// Simple helper to generate a unique string ID
const generateId = () => `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

export const useOfflineSyncStore = create<OfflineSyncState>()(
  persist(
    (set, get) => ({
      queue: [],
      history: [],
      isOnline: typeof navigator !== 'undefined' ? navigator.onLine : true,
      isSyncing: false,

      setOnlineStatus: (isOnline) => {
        // Sync with React Query onlineManager
        onlineManager.setOnline(isOnline);
        
        const previousOnline = get().isOnline;
        set({ isOnline });

        // If transitioning from offline to online, trigger queue sync automatically
        if (isOnline && !previousOnline) {
          get().syncQueue();
        }
      },

      loadOfflineQueue: async () => {
        const checkins = await indexedDbStore.getAllCheckIns();
        if (checkins && checkins.length > 0) {
          set({ queue: checkins });
        }
      },

      enqueueScan: async (ticketCode, eventId) => {
        const newScan: QueuedCheckIn = {
          id: generateId(),
          ticketCode,
          eventId,
          scannedAt: new Date().toISOString(),
          status: 'pending',
          retryCount: 0,
        };

        // Save to IndexedDB first for durability
        try {
          await indexedDbStore.saveCheckIn(newScan);
        } catch (err) {
          console.warn('Failed to persist scan to IndexedDB:', err);
        }

        // Add to queue state
        set((state) => ({
          queue: [...state.queue, newScan],
        }));

        // Try to run sync immediately if online
        if (get().isOnline) {
          get().syncQueue();
        }
      },

      syncQueue: async () => {
        const { queue, isSyncing, isOnline } = get();
        if (isSyncing || queue.length === 0 || !isOnline) return;

        set({ isSyncing: true });

        // Clone queue to process
        const itemsToProcess = [...queue];

        for (const item of itemsToProcess) {
          // Update item status in store to syncing
          set((state) => ({
            queue: state.queue.map((q) =>
              q.id === item.id ? { ...q, status: 'syncing' as const } : q
            ),
          }));

          try {
            // Send request to real backend endpoint
            // We use standard axios, which will include authorization cookies/headers if configured
            await axios.post('/api/venue/check-in', {
              ticket_code: item.ticketCode,
              event_id: item.eventId,
              scanned_at: item.scannedAt,
              client_mutation_id: item.id, // For idempotency check on backend
            });

            // Delete from IndexedDB upon successful sync
            try {
              await indexedDbStore.deleteCheckIn(item.id);
            } catch (dbErr) {
              console.warn('Failed to delete from IndexedDB:', dbErr);
            }

            // If success, move to history and remove from queue
            const completedItem: QueuedCheckIn = {
              ...item,
              status: 'synced' as const,
            };

            set((state) => ({
              queue: state.queue.filter((q) => q.id !== item.id),
              history: [completedItem, ...state.history].slice(0, 100), // keep last 100 scans in history
            }));
          } catch (err: any) {
            console.error(`Failed to sync check-in for ticket ${item.ticketCode}:`, err);
            
            const isNetworkError = !err.response || err.code === 'ERR_NETWORK';

            if (isNetworkError) {
              // Revert to pending, stop sync loop since network is likely down again
              set((state) => ({
                queue: state.queue.map((q) =>
                  q.id === item.id ? { ...q, status: 'pending' as const } : q
                ),
                isSyncing: false,
              }));
              return;
            } else {
              // Severe application error (e.g., ticket invalid, expired, already scanned)
              // Update status to 'failed' and keep in history/queue with error so staff can inspect
              const failedItem: QueuedCheckIn = {
                ...item,
                status: 'failed' as const,
                error: err.response?.data?.message || 'Invalid ticket or validation error',
              };

              // Delete from IndexedDB since we completed trying to sync (it is a logical failure)
              try {
                await indexedDbStore.deleteCheckIn(item.id);
              } catch (dbErr) {
                console.warn('Failed to delete from IndexedDB:', dbErr);
              }

              set((state) => ({
                queue: state.queue.filter((q) => q.id !== item.id),
                history: [failedItem, ...state.history].slice(0, 100),
              }));
            }
          }
        }

        set({ isSyncing: false });
      },

      clearSyncedHistory: () => {
        set((state) => ({
          history: state.history.filter((h) => h.status !== 'synced'),
        }));
      },

      removeFailedScan: async (id) => {
        try {
          await indexedDbStore.deleteCheckIn(id);
        } catch (dbErr) {
          console.warn('Failed to delete from IndexedDB:', dbErr);
        }

        set((state) => ({
          history: state.history.filter((h) => h.id !== id),
        }));
      },
    }),
    {
      name: 'eventiq-offline-checkin-queue',
      partialize: (state) => ({
        queue: state.queue,
        history: state.history,
      }),
    }
  )
);

// Register global connection listeners to sync state
if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    useOfflineSyncStore.getState().setOnlineStatus(true);
  });
  window.addEventListener('offline', () => {
    useOfflineSyncStore.getState().setOnlineStatus(false);
  });
}
