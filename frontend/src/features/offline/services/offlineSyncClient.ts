import type {
  ApplyDueResponse,
  OfflineEnqueueRequest,
  OfflineEnqueueResponse,
} from '../types/OfflineSyncTypes';
import type { OfflineSyncState, OfflineTicket } from '../types';

import { api } from '../../../lib/api';
import { getDeviceToken } from './deviceToken';

async function postJson<T>(url: string, body: any): Promise<T> {
  const response = await api.post<T>(url, body);
  return response.data;
}

export async function enqueueOfflineOperation(
  req: OfflineEnqueueRequest<Record<string, any>>
): Promise<OfflineEnqueueResponse> {
  return postJson<OfflineEnqueueResponse>('/api/offline-sync/enqueue', {
    ...req,
    client_id: req.client_id ?? getDeviceToken(),
  });
}

export async function applyDueOfflineOperations(
  limit = 50
): Promise<ApplyDueResponse> {
  const response = await api.post<ApplyDueResponse>(`/api/offline-sync/apply-due?limit=${limit}`, {});
  return response.data;
}

export async function syncOfflineTickets(
  lastSyncAt?: string,
  syncVersion = 0
): Promise<OfflineSyncState> {
  const headers = { 'X-Device-Token': getDeviceToken() };
  const params = new URLSearchParams();
  if (lastSyncAt) params.set('last_sync_at', lastSyncAt);
  params.set('sync_version', String(syncVersion));

  const response = await api.get<OfflineSyncState>('/api/offline-sync/tickets', {
    headers,
    params,
  });
  return response.data;
}

