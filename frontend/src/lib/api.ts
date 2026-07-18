import axios, {
  type AxiosError,
  type InternalAxiosRequestConfig,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios';
import { getDeviceToken } from '../features/offline/services/deviceToken';

type Env = {
  VITE_API_BASE_URL?: string;
};

const baseURL = ((import.meta as unknown) as { env: Env }).env.VITE_API_BASE_URL ?? '';

export const api = axios.create({
  baseURL,
  withCredentials: true,
});


let isRefreshing = false;
let refreshPromise: Promise<string | null> | null = null;
let queuedRequests: Array<{
  resolve: (token: string | null) => void;
  reject: (err: unknown) => void;
}> = [];

const AUTH_TOKEN_STORAGE_KEY = 'authToken';


function getToken(): string | null {
  try {
    return localStorage.getItem(AUTH_TOKEN_STORAGE_KEY);
  } catch {
    return null;
  }
}

function setToken(token: string | null) {
  try {
    if (!token) {
      localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
      return;
    }
    localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, token);
  } catch {
    // ignore
  }
}



async function refreshAccessToken(): Promise<string | null> {
  // If your backend uses Sanctum/session refresh, replace this endpoint accordingly.
  // Expected response shape: { accessToken: string }.
  const response = await api.post('/auth/refresh');
  const accessToken = response.data?.accessToken ?? null;
  return accessToken;
}

function resolveQueuedRequests(token: string | null) {
  for (const waiter of queuedRequests) {
    waiter.resolve(token);
  }
  queuedRequests = [];
}

function rejectQueuedRequests(err: unknown) {
  for (const waiter of queuedRequests) {
    waiter.reject(err);
  }
  queuedRequests = [];
}


api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = getToken();
  if (token) {
    config.headers = config.headers ?? {};
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (config.headers as any).Authorization = `Bearer ${token}`;
  }

  config.headers = config.headers ?? {};
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (config.headers as any)['X-Device-Token'] = getDeviceToken();
  return config;
});

export type ToastType = 'error' | 'warning' | 'success' | 'info';

export interface ToastMessage {
  id: string;
  type: ToastType;
  title: string;
  description: string;
  duration?: number;
}

type ToastListener = (toast: ToastMessage) => void;
const toastListeners = new Set<ToastListener>();

export function addToastListener(listener: ToastListener) {
  toastListeners.add(listener);
  return () => {
    toastListeners.delete(listener);
  };
}

export function showToast(title: string, description: string, type: ToastType = 'error', duration = 5000) {
  const id = Math.random().toString(36).substring(2, 9);
  const toast: ToastMessage = { id, type, title, description, duration };
  toastListeners.forEach((listener) => {
    try {
      listener(toast);
    } catch (e) {
      console.error('Error triggering toast listener', e);
    }
  });
}

api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error: AxiosError) => {
    const status = error.response?.status;

    // Trigger user notification for connection, database, or configuration errors
    if (!error.response) {
      if (error.message === 'Network Error' || error.code === 'ERR_NETWORK') {
        showToast(
          'Network Error',
          'Unable to connect to the server. Please check your internet connection or server status.',
          'error'
        );
      } else {
        showToast(
          'Connection Error',
          error.message || 'A network error occurred.',
          'error'
        );
      }
    } else {
      const responseData = error.response.data as any;
      const serverMessage = responseData?.message || '';

      // Check for database connection loss (usually 500 Internal Server Error with database/SQL error messages, or 503 Service Unavailable)
      const isDatabaseError = 
        status === 500 && 
        (serverMessage.toLowerCase().includes('database') || 
         serverMessage.toLowerCase().includes('sqlstate') || 
         serverMessage.toLowerCase().includes('connection') ||
         JSON.stringify(responseData).toLowerCase().includes('sqlstate') ||
         JSON.stringify(responseData).toLowerCase().includes('database'));

      if (isDatabaseError) {
        showToast(
          'Database Connection Error',
          'The server is currently unable to communicate with the database. Please try again later.',
          'error'
        );
      } else if (status === 503) {
        showToast(
          'Service Unavailable',
          'The server is temporarily unable to handle the request.',
          'error'
        );
      } else if (status === 403) {
        showToast(
          'Access Denied',
          serverMessage || 'You do not have permission to perform this action.',
          'warning'
        );
      } else if (status === 500) {
        showToast(
          'Server Error',
          serverMessage || 'An unexpected internal server error occurred.',
          'error'
        );
      }
    }

    const originalRequest = error.config as (AxiosRequestConfig & {
      _retry?: boolean;
    }) | null;

    // Avoid refresh loops: if the failing request *is* the refresh endpoint,
    // don't attempt another refresh.
    const requestUrl = (originalRequest?.url ?? '').toString();
    const isRefreshRequest = requestUrl.includes('/auth/refresh');

    if (!isRefreshRequest && status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true;

      if (!refreshPromise) {
        refreshPromise = (async () => {
          try {
            const newToken = await refreshAccessToken();
            return newToken;
          } catch {
            return null;
          } finally {
            isRefreshing = false;
          }
        })();
      }

      // Wait for refresh to complete (single-flight)
      const newToken = await refreshPromise;
      refreshPromise = null;

      if (newToken) {
        setToken(newToken);
        // Update auth header for retried request
        originalRequest.headers = originalRequest.headers ?? {};
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        (originalRequest.headers as any).Authorization = `Bearer ${newToken}`;
        return api.request(originalRequest);
      }

      // Refresh failed: fall back to logout
      if (typeof window !== 'undefined') {
        localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
        window.location.href = '/login';
      }
    }

    return Promise.reject(error);
  },
);




