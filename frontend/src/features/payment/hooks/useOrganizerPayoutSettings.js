import { api } from '../../../lib/api';

export function useOrganizerPayoutSettings() {
  const fetchSettings = async () => {
    const response = await api.get('/organizer/payment-settings');
    return response.data;
  };

  const fetchPayoutMethods = async () => {
    const response = await api.get('/organizer/payout-methods');
    return response.data?.data ?? [];
  };

  return { fetchSettings, fetchPayoutMethods };
}
