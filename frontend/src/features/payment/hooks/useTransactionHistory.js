import { api } from '../../lib/api';
import type { Transaction } from '../types/index';

export function useTransactionHistory() {
  const fetchHistory = async (): Promise<Transaction[]> => {
    const response = await api.get('/user/transactions');
    return response.data.data ?? [];
  };

  const fetchTransaction = async (id: string): Promise<Transaction> => {
    const response = await api.get(`/user/transactions/${id}`);
    return response.data.data;
  };

  return { fetchHistory, fetchTransaction };
}
