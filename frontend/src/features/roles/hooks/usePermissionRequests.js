export const usePermissionRequests = () => {
  return {
    requests: [],
    loading: false,
    error: null,
    fetchRequests: () => {},
    approveRequest: () => {},
    rejectRequest: () => {},
  };
};
