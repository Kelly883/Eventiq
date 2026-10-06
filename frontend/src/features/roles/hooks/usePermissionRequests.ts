import type { PermissionRequest } from '../types';

export interface UsePermissionRequestsResult {
  requests: PermissionRequest[];
  loading: boolean;
  error: string | null;
  fetchRequests: () => void;
  approveRequest: (requestId: string) => void;
  rejectRequest: (requestId: string) => void;
}

export const usePermissionRequests = (): UsePermissionRequestsResult => {
  return {
    requests: [],
    loading: false,
    error: null,
    fetchRequests: () => {},
    approveRequest: () => {},
    rejectRequest: () => {},
  };
};
