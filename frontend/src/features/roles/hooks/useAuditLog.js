export const useAuditLog = () => {
  return {
    auditLogs: [],
    loading: false,
    error: null,
    fetchAuditLogs: () => {},
  };
};
