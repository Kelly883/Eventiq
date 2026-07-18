import type {
  ApplyDueResponse,
  OfflineEnqueueRequest,
  OfflineEnqueueResponse,
} from '../types/OfflineSyncTypes';

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

