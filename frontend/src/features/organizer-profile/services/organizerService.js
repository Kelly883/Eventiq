import { api } from '../../lib/api';

export const organizerService = {
  getPublicProfile: async (organizerId) => {
    const res = await api.get(`/organizers/${organizerId}`);
    return res.data;
  },

  getMyProfile: async () => {
    const res = await api.get('/organizer/me');
    return res.data;
  },

  getProfile: async () => {
    const res = await api.get('/organizer/profile');
    return res.data;
  },

  updateProfile: async (data) => {
    const res = await api.put('/organizer/profile', data);
    return res.data;
  },

  getPublicEvents: async (organizerId) => {
    const res = await api.get(`/organizers/${organizerId}/events`);
    return res.data;
  },
};
