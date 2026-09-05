import { api } from '../../../lib/api';

export const complianceService = {
  getAuditLogs: async (params = {}) => {
    const response = await api.get('/admin/compliance/audit-logs', { params });
    return response.data;
  },

  getAuditLog: async (logId) => {
    const response = await api.get(`/admin/compliance/audit-logs/${logId}`);
    return response.data?.data ?? response.data;
  },

  exportAuditLogs: async (params = {}) => {
    const response = await api.get('/admin/compliance/audit-logs/export', { params });
    return response.data;
  },

  bulkTagAuditLogs: async (logIds, tag) => {
    const response = await api.post('/admin/compliance/audit-logs/bulk-tag', { logIds, tag });
    return response.data;
  },

  getComplianceReports: async () => {
    const response = await api.get('/admin/compliance/reports');
    return response.data?.reports ?? [];
  },

  generateComplianceReport: async (reportCode, filters = {}) => {
    const response = await api.post('/admin/compliance/reports/generate', { reportCode, filters });
    return response.data;
  },

  downloadComplianceReport: async (reportId) => {
    const response = await api.get(`/admin/compliance/reports/${reportId}/download`);
    return response.data;
  },

  getComplianceChecklist: async () => {
    const response = await api.get('/admin/compliance/checklist');
    return response.data?.checklist ?? [];
  },
};
