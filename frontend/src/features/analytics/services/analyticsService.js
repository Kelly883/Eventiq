import { api } from '../../../lib/api';

export const analyticsService = {
  getAnalyticsSummary: async (eventId) => {
    const response = await api.get(`/organizer/events/${eventId}/analytics/summary`);
    return response.data?.metrics ?? response.data;
  },
  getSalesVelocity: async (eventId, interval = 'daily') => {
    const response = await api.get(`/organizer/events/${eventId}/analytics/sales-velocity`, {
      params: { interval }
    });
    return response.data?.data ?? response.data;
  },
  getDetailedAnalytics: async (eventId) => {
    const response = await api.get(`/organizer/events/${eventId}/analytics/detailed`);
    return response.data;
  },
  getEventComparison: async (eventIds) => {
    const response = await api.get('/organizer/analytics/comparison', {
      params: { eventIds }
    });
    return response.data?.comparison ?? response.data;
  },
};

