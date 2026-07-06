// Mock API service for payouts
// In a real app, this would make actual API calls

const BASE_URL = '/api';

export const payoutService = {
  // Organizer endpoints
  getOrganizerPayouts: async (filters = {}) => {
    return { data: [], meta: { total: 0, page: 1, perPage: 10 } };
  },

  getOrganizerPayoutSummary: async () => {
    return { total_pending: 0, total_processed: 0, total_earned: 0, next_payout: 0 };
  },

  getPayoutDetails: async (payoutId) => {
    return null;
  },

  // Admin endpoints
  getAdminSettlements: async (filters = {}) => {
    return { data: [], meta: { total: 0, page: 1, perPage: 10 } };
  },

  getAdminSettlementSummary: async () => {
    return { total_settled: 0, total_pending: 0, total_platform_fee: 0 };
  },

  processPayout: async (payoutId) => {
    return null;
  },

  getSettlementPolicies: async () => {
    return [];
  },

  updateSettlementPolicy: async (policyId, data) => {
    return null;
  },

  exportPayouts: async (filters = {}) => {
    // Mock export
  },
};
