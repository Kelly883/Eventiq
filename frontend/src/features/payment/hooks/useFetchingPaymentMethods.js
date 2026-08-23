import { api } from '../../lib/api';

export function useFetchingPaymentMethods() {
  const fetchPaymentMethods = async () => {
    const response = await api.get('/user/payment-methods');
    return response.data.data ?? [];
  };

  const setDefaultPaymentMethod = async (id) => {
    const response = await api.post(`/user/payment-methods/${id}/set-default`);
    return response.data.data;
  };

  return { fetchPaymentMethods, setDefaultPaymentMethod };
}
