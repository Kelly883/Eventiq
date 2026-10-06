import type { Role } from '../types';

export interface UseRoleListResult {
  roles: Role[];
  loading: boolean;
  error: string | null;
  fetchRoles: () => void;
}

export const useRoleList = (): UseRoleListResult => {
  return {
    roles: [],
    loading: false,
    error: null,
    fetchRoles: () => {},
  };
};
