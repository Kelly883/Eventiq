import { useQuery } from '@tanstack/react-query';
import { api } from '../../../lib/api';

// The editor predates the current API resource names. Keep that compatibility
// mapping at the API boundary so the page renders real template data rather
// than depending on a second, legacy response shape.
const toEditorTemplate = (template) => ({
  ...template,
  key: template.key ?? template.type ?? '',
  status: template.status ?? (template.isActive ? 'active' : 'draft'),
  content: template.content ?? template.mjmlBody ?? template.htmlBody ?? '',
});

const fetchEmailTemplates = async () => {
  const response = await api.get('/email-templates');
  const templates = response.data?.data || response.data || [];
  return templates.map(toEditorTemplate);
};

export const useEmailTemplates = () => {
  return useQuery({
    queryKey: ['email-templates'],
    queryFn: fetchEmailTemplates,
  });
};
