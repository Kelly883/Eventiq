import { api } from '../../lib/api';
import type { PaymentMethod } from '../types/payment';

export function useFetchingPaymentMethods() {
  const fetchPaymentMethods = async (): Promise<PaymentMethod[]> => {
    const response = await api.get('/user/payment-methods');
    return response.data.data ?? [];
  };

  const setDefaultPaymentMethod = async (id: string): Promise<PaymentMethod> => {
    const response = await api.post(`/user/payment-methods/${id}/set-default`);
    return response.data.data;
  };

  return { fetchPaymentMethods, setDefaultPaymentMethod };
}
