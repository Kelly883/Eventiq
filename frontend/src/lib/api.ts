import axios, {
  type AxiosError,
  type InternalAxiosRequestConfig,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios';
import { getDeviceToken } from '../features/offline/services/deviceToken';

/* -------------------------------------------------------------------------- */
/*                              Toast System                                  */
/* -------------------------------------------------------------------------- */

type Toast = { id: number; title: string; description: string; type: string; duration?: number };
type ToastListener = (toast: Toast) => void;

let _toastId = 0;
const _toastListeners = new Set<ToastListener>();

export function addToastListener(listener: ToastListener): () => void {
  _toastListeners.add(listener);
  return () => { _toastListeners.delete(listener); };
}

export function showToast(title: string, description: string, type: string = 'info', duration?: number) {
  const toast: Toast = { id: ++_toastId, title, description, type, duration };
  if (_toastListeners.size > 0) {
    _toastListeners.forEach((fn) => fn(toast));
  } else if (typeof window !== 'undefined') {
    console.warn(`[toast:${type}] ${title}: ${description}`);
  }
}

type Env = {
  VITE_API_BASE_URL?: string;
};

const baseURL = ((import.meta as unknown) as { env: Env }).env.VITE_API_BASE_URL ?? '';

export const api = axios.create({
  baseURL,
  withCredentials: true,
});

/* -------------------------------------------------------------------------- */
/*                               Refresh State                                */
/* -------------------------------------------------------------------------- */

let isRefreshing = false;
let refreshPromise: Promise<boolean> | null = null;
let queuedRequests: Array<{
  onSuccess: (response: any) => void;
  onFailure: (error: AxiosError) => void;
}> = [];

/* -------------------------------------------------------------------------- */
/*                       Refresh CSRF Cookie (Sanctum)                        */
/* -------------------------------------------------------------------------- */

async function refreshCsrf(): Promise<boolean> {
  try {
    await api.get('/sanctum/csrf-cookie', {
      withCredentials: true,
    });
    return true;
  } catch {
    return false;
  }
}

/* -------------------------------------------------------------------------- */
/*                         Response Interceptor                               */
/* -------------------------------------------------------------------------- */

api.interceptors.response.use(
  (response: AxiosResponse) => response,

  async (error: AxiosError) => {
    const originalConfig = error.config as InternalAxiosRequestConfig & {
      _retry?: boolean;
    };

    // If we already tried to refresh, don't try again
    if (error.response?.status === 401 && originalConfig._retry) {
      return Promise.reject(error);
    }

    // If it's not a 401, just reject
    if (error.response?.status !== 401) {
      return Promise.reject(error);
    }

    // Mark this as a retry attempt
    originalConfig._retry = true;

    // Show session expired toast
    showToast(
      'Session expired',
      'Your session has ended. Please log in again.',
      'warning'
    );

    // If refreshing is already in progress, queue this request
    if (isRefreshing) {
      queuedRequests.push({
        onSuccess: (response: any) => {
          // Re-run the original request via the caller
          // This is handled by the caller checking queuedRequests
        },
        onFailure: (error: AxiosError) => {
          // Handle failure
        },
      });
      return Promise.reject(error);
    }

    // Start refresh process
    isRefreshing = true;

    // Try to refresh the CSRF cookie / session
    const csrfRefreshed = await refreshCsrf();

    isRefreshing = false;

    // Clear queued requests
    queuedRequests.forEach(q => {
      // No-op: queued requests will be handled by caller
    });
    queuedRequests = [];

    if (csrfRefreshed) {
      // CSRF refreshed successfully - retry the original request
      originalConfig._retry = undefined;
      return api(originalConfig);
    }

    // CSRF refresh failed - session is truly expired
    // Dispatch session-expired event so AuthContext and app can redirect to login
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('session-expired', {
        detail: { reason: 'csrf-refresh-failed' }
      }));
    }
    return Promise.reject(error);
  }
);

/* -------------------------------------------------------------------------- */
/*                         Export Type Helpers                                */
/* -------------------------------------------------------------------------- */

export type ApiResponse<T = any> = {
  data: T;
  status: number;
  statusText: string;
  headers: any;
  config: any;
};