import { create } from 'zustand';
import { api } from '../../../lib/api';

export const useApiKeyStore = create((set, get) => ({
  apiKeys: [],
  scopes: {},
  rawKey: null,
  isLoading: false,
  error: null,
  async load() {
    set({ isLoading: true, error: null });
    try {
      const [keysResponse, scopesResponse] = await Promise.all([
        api.get('/organizer/developer/api-keys'),
        api.get('/organizer/developer/api-keys/scopes'),
      ]);

      set({
        apiKeys: keysResponse.data?.data ?? [],
        scopes: scopesResponse.data?.data ?? {},
        isLoading: false,
      });
    } catch (error) {
      set({ error: error?.response?.data?.message ?? 'Unable to load API keys.', isLoading: false });
    }
  },
  async createKey(payload) {
    set({ isLoading: true, error: null, rawKey: null });
    try {
      const response = await api.post('/organizer/developer/api-keys', payload);
      set({
        apiKeys: [response.data.data, ...get().apiKeys],
        rawKey: response.data.raw_key,
        isLoading: false,
      });
    } catch (error) {
      set({ error: error?.response?.data?.message ?? 'Unable to create API key.', isLoading: false });
    }
  },
  async revokeKey(apiKeyId) {
    set({ isLoading: true, error: null });
    try {
      const response = await api.post(`/organizer/developer/api-keys/${apiKeyId}/revoke`);
      set({
        apiKeys: get().apiKeys.map((apiKey) => (apiKey.id === apiKeyId ? response.data.data : apiKey)),
        isLoading: false,
      });
    } catch (error) {
      set({ error: error?.response?.data?.message ?? 'Unable to revoke API key.', isLoading: false });
    }
  },
  clearRawKey() {
    set({ rawKey: null });
  },
}));
