import axios, {
  type AxiosError,
  type InternalAxiosRequestConfig,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios';
import { getDeviceToken, restoreDeviceToken } from '../features/offline/services/deviceToken';

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

function isAuthenticationRequest(url?: string): boolean {
  return Boolean(url?.includes('/auth/'));
}

const rawBaseURL = (
  ((import.meta as unknown) as { env: Env }).env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'
).replace(/\/+$/, '');

// Ensure the axios baseURL always ends with /api so requests like
// `/auth/register` resolve to `/api/auth/register`, which is where
// Laravel's api.php routes are mounted. The previous "use as-is"
// approach broke production where RENDER_EXTERNAL_URL has no /api
// suffix (e.g. https://eventiq-api.onrender.com → 404 on
// /auth/register, CORS missing → browser reports Network Error).
// The CSRF cookie itself lives outside /api at /sanctum/csrf-cookie,
// so strip that prefix back off for the token fetch.
const normalizedBaseURL = rawBaseURL.endsWith('/api') ? rawBaseURL : `${rawBaseURL}/api`;
const csrfCookieUrl = rawBaseURL.replace(/\/api$/, '') + '/sanctum/csrf-cookie';

export const api = axios.create({
  baseURL: normalizedBaseURL,
  withCredentials: true,
  // Sanctum's stateful middleware requires the X-XSRF-TOKEN header on cross-
  // origin requests from the SPA (the vite proxy is not used for the absolute
  // baseURL). axios' default withXSRFToken filter only attaches the token for
  // SAME-ORIGIN requests, so login always returned 419 against the real
  // backend. We still only send it when the XSRF-TOKEN cookie is present —
  // which is only set by refreshCsrf()/auth flows — so the CSRF guarantee
  // (header must match cookie) is preserved.
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  withXSRFToken: true,
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

export async function refreshCsrf(): Promise<boolean> {
  try {
    await axios.get(csrfCookieUrl, {
      withCredentials: true,
    });
    return true;
  } catch {
    return false;
  }
}

/* -------------------------------------------------------------------------- */
/*                               Request Interceptor                          */
/* -------------------------------------------------------------------------- */

api.interceptors.request.use(async (config) => {
  if (typeof window !== 'undefined') {
    // Await the IDB→localStorage recovery BEFORE minting a token: the request
    // header must carry the persisted device identity, not a fresh one, when
    // localStorage was evicted but IndexedDB still holds the token.
    await restoreDeviceToken();
    const deviceToken = getDeviceToken();
    if (deviceToken) {
      config.headers = config.headers ?? {};
      config.headers['X-Device-Token'] = deviceToken;
    }
  }
  return config;
});

/* -------------------------------------------------------------------------- */
/*                         Response Interceptor                               */
/* -------------------------------------------------------------------------- */

api.interceptors.response.use(
  (response: AxiosResponse) => response,

  async (error: AxiosError) => {
    const originalConfig = error.config as InternalAxiosRequestConfig & {
      _retry?: boolean;
    };

    // Authentication endpoints deliberately return 401 for guests and invalid
    // credentials. Treating those expected responses as a session expiry makes
    // the login page flash a misleading toast on every fresh visit.
    if (error.response?.status === 401 && isAuthenticationRequest(originalConfig?.url)) {
      return Promise.reject(error);
    }

    // The CSRF refresh cannot renew an expired Sanctum session. A second 401
    // therefore confirms expiration and must notify the router.
    if (error.response?.status === 401 && originalConfig._retry) {
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('session-expired', {
          detail: { reason: 'authenticated-request-rejected' }
        }));
      }
      return Promise.reject(error);
    }

    // If it's not a 401, just reject
    if (error.response?.status !== 401) {
      return Promise.reject(error);
    }

    // Mark this as a retry attempt
    const isFirstRetry = !originalConfig?._retry;
    originalConfig._retry = true;

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
      // No-op: queued requests will be honest failure
    });
    queuedRequests = [];

    if (csrfRefreshed) {
      // CSRF refreshed successfully - retry the original request
      // Keep _retry = true so repeated 401s are properly guarded
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
