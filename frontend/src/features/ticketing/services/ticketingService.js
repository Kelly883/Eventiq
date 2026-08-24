import { api } from '../../../lib/api';

export const ticketingService = {
  getTicketTiers: async (eventId) => {
    const res = await api.get(`/organizer/events/${eventId}`);
    const data = res.data?.event || res.data?.data || res.data;
    const tiers = data?.ticket_tiers || data?.ticketTiers || data?.tiers || [];
    return { event: data, tiers };
  },
  updateTicketTiers: async (eventId, tiers) => {
    // Backend expects PUT /organizer/events/{event}/ticketing with { tiers: [...] }
    // Each tier must contain event_id, name, description, price, quantity etc. per UpdateTicketTiersRequest
    const payload = {
      tiers: tiers.map((t, idx) => ({
        id: t.id || null,
        event_id: Number(eventId),
        name: t.name,
        description: t.description || t.name || `Tier ${idx + 1}`,
        price: Number(t.price) || 0,
        quantity: t.quantity ? Number(t.quantity) : null,
        sales_start_date: t.sales_start_date || null,
        sales_end_date: t.sales_end_date || null,
        is_active: t.is_active ?? true,
        tier_order: idx,
        status: t.status || 'published',
        currency: t.currency || 'NGN',
        max_per_customer: t.max_per_customer || null,
        min_purchase: t.min_purchase || 1,
        max_purchase: t.max_purchase || null,
        // preserve other fields if present
        ...(t.early_bird_price ? { early_bird_price: Number(t.early_bird_price) } : {}),
        ...(t.early_bird_end_date ? { early_bird_end_date: t.early_bird_end_date } : {}),
      })),
    };
    const res = await api.put(`/organizer/events/${eventId}/ticketing`, payload);
    return res.data;
  },
};
