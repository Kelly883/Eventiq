import type {
  ApplyDueResponse,
  OfflineEnqueueRequest,
  OfflineEnqueueResponse,
} from '../types/OfflineSyncTypes';

const API_BASE = '';

async function postJson<T>(url: string, body: any): Promise<T> {
  const res = await fetch(API_BASE + url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      // Assumes caller will attach auth via cookies or global header middleware.
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`OfflineSync request failed (${res.status}): ${text}`);
  }

  return (await res.json()) as T;
}

export async function enqueueOfflineOperation(
  req: OfflineEnqueueRequest<Record<string, any>>
): Promise<OfflineEnqueueResponse> {
  return postJson<OfflineEnqueueResponse>('/api/offline-sync/enqueue', req);
}

export async function applyDueOfflineOperations(
  limit = 50
): Promise<ApplyDueResponse> {
  const res = await fetch(API_BASE + `/api/offline-sync/apply-due?limit=${limit}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({}),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`OfflineSync apply-due failed (${res.status}): ${text}`);
  }

  return (await res.json()) as ApplyDueResponse;
}

