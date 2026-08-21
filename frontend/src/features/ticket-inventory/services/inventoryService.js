export const inventoryService = {
  getSummary: async (eventId) => {
    const res = await fetch(`/api/organizer/events/${eventId}/inventory/summary`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('authToken')}` },
    });
    if (!res.ok) throw new Error('Failed to fetch inventory summary');
    return res.json();
  },
  getInventory: async (eventId) => {
    const res = await fetch(`/api/organizer/events/${eventId}/inventory`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('authToken')}` },
    });
    if (!res.ok) throw new Error('Failed to fetch inventory');
    return res.json();
  },
  adjustInventory: async (eventId, inventoryId, data) => {
    const res = await fetch(`/api/organizer/events/${eventId}/inventory/${inventoryId}/adjust`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${localStorage.getItem('authToken')}`,
      },
      body: JSON.stringify(data),
    });
    if (!res.ok) throw new Error('Failed to adjust inventory');
    return res.json();
  },
  getAuditLogs: async (eventId) => {},
  getLowStockAlerts: async (eventId) => {},
  exportInventory: async (eventId) => {},
};
