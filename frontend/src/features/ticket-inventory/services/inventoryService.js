import { api } from '../../../lib/api';

export const inventoryService = {
  getSummary: async (eventId) => {
    const res = await api.get(`/organizer/events/${eventId}/inventory/summary`);
    if (!res.status) throw new Error('Failed to fetch inventory summary');
    return res.data;
  },
  getInventory: async (eventId) => {
    const res = await api.get(`/organizer/events/${eventId}/inventory`);
    if (!res.status) throw new Error('Failed to fetch inventory');
    return res.data;
  },
  adjustInventory: async (eventId, inventoryId, data) => {
    const res = await api.post(`/organizer/events/${eventId}/inventory/${inventoryId}/adjust`, data);
    if (!res.status) throw new Error('Failed to adjust inventory');
    return res.data;
  },
  getAuditLogs: async (eventId) => {},
  getLowStockAlerts: async (eventId) => {},
  exportInventory: async (eventId) => {},
};
