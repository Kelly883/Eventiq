import { useQuery } from '@tanstack/react-query';
import { api } from '../../../lib/api';

const fetchEmailTemplates = async () => {
  const response = await api.get('/admin/email-templates');
  return response.data?.data || response.data || [];
};

export const useEmailTemplates = () => {
  return useQuery({
    queryKey: ['email-templates'],
    queryFn: fetchEmailTemplates,
  });
};
