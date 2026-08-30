import { api } from '../../../lib/api';

export const inventoryService = {
  getSummary: async (eventId) => {
    const res = await api.get(`/organizer/events/${eventId}/inventory/summary`);
    if (!res.status) throw new Error('Failed to fetch inventory summary');
    return res.data;
  },
  getInventory: async (eventId) => {
    try {
      const res = await api.get(`/organizer/events/${eventId}/inventory`);
      if (!res.status) throw new Error('Failed to fetch inventory');
      return res.data;
    } catch (e) {
      // Backend route not yet implemented - return empty data instead of crashing
      return [];
    }
  },
  adjustInventory: async (eventId, inventoryId, data) => {
    try {
      const res = await api.post(`/organizer/events/${eventId}/inventory/${inventoryId}/adjust`, data);
      if (!res.status) throw new Error('Failed to adjust inventory');
      return res.data;
    } catch (e) {
      // Backend route not yet implemented
      return { success: false, message: 'Inventory adjustment not yet implemented' };
    }
  },
  getAuditLogs: async (eventId) => [],
  getLowStockAlerts: async (eventId) => [],
  exportInventory: async (eventId) => [],
};
