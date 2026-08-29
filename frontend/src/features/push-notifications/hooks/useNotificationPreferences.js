import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, showToast } from '../../../lib/api';

export const DEFAULT_PREFERENCES = {
  pushNotificationsEnabled: true,
  pushOrderConfirmation: true,
  pushEventReminder: true,
  pushCheckinAlert: true,
  pushPromotionalOffers: false,
};

const fetchPreferences = async () => {
  const response = await api.get('/push-notifications/preferences');
  return { ...DEFAULT_PREFERENCES, ...(response.data?.data || response.data || {}) };
};

const savePreferences = async (payload) => {
  const response = await api.put('/push-notifications/preferences', payload);
  return response.data?.data || response.data || payload;
};

export const useNotificationPreferences = () => {
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: ['push-notification-preferences'],
    queryFn: fetchPreferences,
    staleTime: 60_000,
  });

  const saveMutation = useMutation({
    mutationFn: savePreferences,
    onSuccess: (saved) => {
      queryClient.setQueryData(['push-notification-preferences'], saved);
    },
    onError: (err) => {
      showToast(
        'Save failed',
        err?.response?.data?.message || 'Failed to save notification preferences.',
        'error'
      );
    },
  });

  return {
    preferences: query.data,
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error,
    save: saveMutation.mutate,
    isSaving: saveMutation.isPending,
  };
};
