export interface UsePermissionUpdateResult {
  updatePermission: () => void;
  loading: boolean;
  error: string | null;
}

export const usePermissionUpdate = (): UsePermissionUpdateResult => {
  return {
    updatePermission: () => {},
    loading: false,
    error: null,
  };
};
