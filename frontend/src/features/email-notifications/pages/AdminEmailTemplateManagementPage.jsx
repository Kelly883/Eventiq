import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '../../../lib/api';
import TemplateEditor from '../components/TemplateEditor';
import Skeleton from '../../../components/Skeleton';

const fetchEmailTemplates = async () => {
  const response = await api.get('/admin/email-templates');
  return response.data?.data || response.data || [];
};

const AdminEmailTemplateManagementPage = () => {
  const { data: templates, isLoading, isError, error } = useQuery({
    queryKey: ['email-templates'],
    queryFn: fetchEmailTemplates,
  });

  const [selectedTemplate, setSelectedTemplate] = React.useState(null);
  const [mjmlContent, setMjmlContent] = React.useState('');

  React.useEffect(() => {
    if (templates && templates.length > 0 && !selectedTemplate) {
      setSelectedTemplate(templates[0]);
      setMjmlContent(templates[0]?.content || '');
    }
  }, [templates, selectedTemplate]);

  const handleTemplateSelect = (template) => {
    setSelectedTemplate(template);
    setMjmlContent(template.content || '');
  };

  if (isLoading) {
    return (
      <div className="p-6 max-w-6xl mx-auto">
        <Skeleton variant="text" className="h-8 w-64 mb-6" />
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-1">
            <Skeleton variant="card" className="h-96" />
          </div>
          <div className="lg:col-span-2">
            <Skeleton variant="card" className="h-96" />
          </div>
        </div>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="p-6 max-w-6xl mx-auto">
        <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
          <h3 className="font-bold">Failed to load email templates</h3>
          <p className="text-sm mt-1">{error?.message || 'An error occurred while fetching templates.'}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl font-extrabold text-slate-900">Email Templates</h1>
        <p className="text-sm text-slate-500 mt-1">
          Manage notification templates for transactional emails sent to users.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Template List Sidebar */}
        <div className="lg:col-span-1">
          <div className="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-100">
              <h2 className="font-semibold text-slate-800">Templates</h2>
              <p className="text-xs text-slate-500 mt-0.5">{templates?.length || 0} templates available</p>
            </div>
            <div className="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
              {templates && templates.length > 0 ? (
                templates.map((template) => (
                  <button
                    key={template.id}
                    onClick={() => handleTemplateSelect(template)}
                    className={`w-full text-left p-4 hover:bg-slate-50 transition-colors ${
                      selectedTemplate?.id === template.id ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''
                    }`}
                  >
                    <div className="font-medium text-slate-800">{template.name}</div>
                    <div className="text-xs text-slate-500 mt-0.5">{template.subject}</div>
                    <div className="flex items-center gap-2 mt-2">
                      <span className={`text-[10px] font-medium px-2 py-0.5 rounded-full ${
                        template.status === 'active'
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-slate-100 text-slate-600'
                      }`}>
                        {template.status === 'active' ? 'Active' : 'Draft'}
                      </span>
                      <span className="text-[10px] text-slate-400">{template.key}</span>
                    </div>
                  </button>
                ))
              ) : (
                <div className="p-4 text-center text-slate-500 text-sm">
                  No templates found. Create your first email template.
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Template Editor */}
        <div className="lg:col-span-2">
          {selectedTemplate ? (
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h2 className="font-semibold text-slate-800">{selectedTemplate.name}</h2>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Subject: {selectedTemplate.subject}
                  </p>
                </div>
                <button
                  onClick={() => {}}
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                  Save Changes
                </button>
              </div>
              <TemplateEditor content={mjmlContent} onChange={setMjmlContent} />
            </div>
          ) : (
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-12 text-center">
              <span className="text-4xl block mb-4">✉️</span>
              <h3 className="font-semibold text-slate-800">Select a template to edit</h3>
              <p className="text-sm text-slate-500 mt-1">
                Choose a template from the list to start editing.
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminEmailTemplateManagementPage;
