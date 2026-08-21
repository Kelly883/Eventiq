import { api } from '../../lib/api';
import type { OrganizerPaymentSettings } from '../types/payment';

export function useOrganizerPayoutSettings() {
  const fetchSettings = async (): Promise<OrganizerPaymentSettings> => {
    const response = await api.get('/organizer/payment-settings');
    return response.data.data;
  };

  const updateSettings = async (payload: Partial<OrganizerPaymentSettings>): Promise<OrganizerPaymentSettings> => {
    const response = await api.put('/organizer/payment-settings', payload);
    return response.data.data;
  };

  return { fetchSettings, updateSettings };
}
