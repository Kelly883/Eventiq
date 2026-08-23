import { api } from '../../lib/api';

export function useOrganizerPayoutSettings() {
  const fetchSettings = async () => {
    const response = await api.get('/organizer/payment-settings');
    return response.data.data;
  };

  const updateSettings = async (payload) => {
    const response = await api.put('/organizer/payment-settings', payload);
    return response.data.data;
  };

  return { fetchSettings, updateSettings };
}
