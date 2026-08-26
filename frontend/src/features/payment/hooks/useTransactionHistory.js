import { api } from '../../lib/api';

export function useTransactionHistory() {
  const fetchHistory = async () => {
    const response = await api.get('/user/transactions');
    return response.data.data ?? [];
  };

  const fetchTransaction = async (id) => {
    const response = await api.get(`/user/transactions/${id}`);
    return response.data.data;
  };

  return { fetchHistory, fetchTransaction };
}
