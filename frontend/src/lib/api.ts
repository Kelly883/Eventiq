import axios, {
  type AxiosError,
  type InternalAxiosRequestConfig,
  type AxiosRequestConfig,
} from 'axios';

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

function getToken(): string | null {
  // Token is stored in an httpOnly cookie; JS cannot read it.
  return null;
}

function setToken(_token: string | null) {
  // No-op: cookie is set by backend.
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
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const status = error.response?.status;

    const originalRequest = error.config as (AxiosRequestConfig & {
      _retry?: boolean;
    }) | null;

    if (status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true;

      if (!refreshPromise) {
        refreshPromise = (async () => {
          try {
            const newToken = await refreshAccessToken();
            return newToken;
          } catch (e) {
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
        localStorage.removeItem('authToken');
        window.location.href = '/login';
      }

    }

    return Promise.reject(error);
  },
);



