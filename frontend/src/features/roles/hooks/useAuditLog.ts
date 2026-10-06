import type { AuditLog } from '../types';

export interface UseAuditLogResult {
  auditLogs: AuditLog[];
  loading: boolean;
  error: string | null;
  fetchAuditLogs: () => void;
}

export const useAuditLog = (): UseAuditLogResult => {
  return {
    auditLogs: [],
    loading: false,
    error: null,
    fetchAuditLogs: () => {},
  };
};
