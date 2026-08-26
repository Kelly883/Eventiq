import React from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../../../lib/api';
import { showToast } from '../../../lib/api';
import TemplateEditor from '../components/TemplateEditor';
import Skeleton from '../../../components/Skeleton';

const fetchEmailTemplates = async () => {
  const response = await api.get('/admin/email-templates');
  return response.data?.data || response.data || [];
};

const saveEmailTemplate = async ({ id, content }) => {
  const response = await api.put(`/admin/email-templates/${id}`, { content });
  return response.data?.data || response.data;
};

const createEmailTemplate = async ({ name, subject, key, content }) => {
  const response = await api.post('/admin/email-templates', { name, subject, key, content });
  return response.data?.data || response.data;
};

const deleteEmailTemplate = async (id) => {
  await api.delete(`/admin/email-templates/${id}`);
};

const templateVariables = [
  { variable: '{{user.name}}', description: 'Full name of the recipient' },
  { variable: '{{user.email}}', description: 'Email address of the recipient' },
  { variable: '{{event.title}}', description: 'Title of the event' },
  { variable: '{{event.date}}', description: 'Date of the event' },
  { variable: '{{event.venue}}', description: 'Venue name' },
  { variable: '{{ticket.code}}', description: 'Unique ticket code' },
  { variable: '{{ticket.type}}', description: 'Ticket type/ tier name' },
  { variable: '{{order.id}}', description: 'Order reference number' },
  { variable: '{{order.total}}', description: 'Order total amount' },
  { variable: '{{company.name}}', description: 'Your company name' },
];

const CreateTemplateModal = ({ isOpen, onClose, onSubmit, isLoading }) => {
  const [formData, setFormData] = React.useState({ name: '', subject: '', key: '' });
  const [errors, setErrors] = React.useState({});

  const handleChange = (field) => (e) => {
    setFormData((prev) => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) {
      setErrors((prev) => ({ ...prev, [field]: '' }));
    }
  };

  const validate = () => {
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = 'Template name is required';
    if (!formData.subject.trim()) newErrors.subject = 'Email subject is required';
    if (!formData.key.trim()) newErrors.key = 'Template key is required';
    else if (!/^[a-z0-9_]+$/.test(formData.key)) newErrors.key = 'Key must contain only lowercase letters, numbers, and underscores';
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit(formData);
    setFormData({ name: '', subject: '', key: '' });
  };

  const handleClose = () => {
    setFormData({ name: '', subject: '', key: '' });
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={handleClose} />
      <div className="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <h2 className="text-lg font-semibold text-slate-900">Create New Template</h2>
          <button
            onClick={handleClose}
            className="p-1 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Template Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.name}
              onChange={handleChange('name')}
              placeholder="e.g., Welcome Email"
              className={`w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
                errors.name ? 'border-red-300' : 'border-slate-200'
              }`}
            />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Email Subject <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.subject}
              onChange={handleChange('subject')}
              placeholder="e.g., Welcome to {{event.title}}!"
              className={`w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
                errors.subject ? 'border-red-300' : 'border-slate-200'
              }`}
            />
            {errors.subject && <p className="mt-1 text-xs text-red-600">{errors.subject}</p>}
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Template Key <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              value={formData.key}
              onChange={handleChange('key')}
              placeholder="e.g., welcome_email"
              className={`w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
                errors.key ? 'border-red-300' : 'border-slate-200'
              }`}
            />
            {errors.key && <p className="mt-1 text-xs text-red-600">{errors.key}</p>}
            <p className="mt-1 text-xs text-slate-500">Lowercase letters, numbers, and underscores only</p>
          </div>

          <div className="bg-slate-50 rounded-lg p-3">
            <p className="text-xs text-slate-600">
              <span className="font-medium">Note:</span> You'll be able to edit the template content after creation.
            </p>
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={handleClose}
              className="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white text-sm font-medium rounded-lg transition-colors"
            >
              {isLoading ? 'Creating...' : 'Create Template'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const DeleteConfirmModal = ({ isOpen, onClose, onConfirm, templateName, isLoading }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
        <div className="p-4">
          <div className="flex items-center gap-3 mb-4">
            <div className="p-2 bg-red-100 rounded-full">
              <svg className="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </div>
            <div>
              <h3 className="font-semibold text-slate-900">Delete Template</h3>
              <p className="text-sm text-slate-500">This action cannot be undone.</p>
            </div>
          </div>
          <p className="text-sm text-slate-600 mb-4">
            Are you sure you want to delete <span className="font-medium">"{templateName}"</span>?
          </p>
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={onConfirm}
              disabled={isLoading}
              className="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white text-sm font-medium rounded-lg transition-colors"
            >
              {isLoading ? 'Deleting...' : 'Delete'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

const PreviewModal = ({ isOpen, onClose, content, templateName }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] flex flex-col">
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <div>
            <h2 className="text-lg font-semibold text-slate-900">Preview: {templateName}</h2>
            <p className="text-xs text-slate-500">Rendered preview of your email template</p>
          </div>
          <button
            onClick={onClose}
            className="p-1 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div className="flex-1 overflow-y-auto p-4 bg-slate-100">
          <div className="bg-white rounded-lg shadow-sm overflow-hidden">
            <div
              className="prose max-w-none p-6"
              dangerouslySetInnerHTML={{ __html: content }}
            />
          </div>
        </div>
        <div className="p-4 border-t border-slate-200 bg-slate-50">
          <p className="text-xs text-slate-500 text-center">
            Preview shows raw HTML output. Actual email rendering may vary by client.
          </p>
        </div>
      </div>
    </div>
  );
};

const AdminEmailTemplateManagementPage = () => {
  const queryClient = useQueryClient();
  const { data: templates, isLoading, isError, error } = useQuery({
    queryKey: ['email-templates'],
    queryFn: fetchEmailTemplates,
  });

  const [selectedTemplate, setSelectedTemplate] = React.useState(null);
  const [mjmlContent, setMjmlContent] = React.useState('');
  const [hasChanges, setHasChanges] = React.useState(false);
  const [showCreateModal, setShowCreateModal] = React.useState(false);
  const [showDeleteModal, setShowDeleteModal] = React.useState(false);
  const [showPreviewModal, setShowPreviewModal] = React.useState(false);
  const [showVariables, setShowVariables] = React.useState(false);

  const saveMutation = useMutation({
    mutationFn: saveEmailTemplate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Template saved', 'Your changes have been saved successfully.', 'success');
      setHasChanges(false);
    },
    onError: (err) => {
      showToast('Save failed', err?.message || 'Failed to save template. Please try again.', 'error');
    },
  });

  const createMutation = useMutation({
    mutationFn: createEmailTemplate,
    onSuccess: (newTemplate) => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Template created', 'New email template has been created.', 'success');
      setSelectedTemplate(newTemplate);
      setMjmlContent(newTemplate.content || '');
      setHasChanges(false);
      setShowCreateModal(false);
    },
    onError: (err) => {
      showToast('Creation failed', err?.message || 'Failed to create template. Please try again.', 'error');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: deleteEmailTemplate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Template deleted', 'Email template has been deleted.', 'success');
      setSelectedTemplate(null);
      setMjmlContent('');
      setHasChanges(false);
      setShowDeleteModal(false);
    },
    onError: (err) => {
      showToast('Deletion failed', err?.message || 'Failed to delete template. Please try again.', 'error');
    },
  });

  React.useEffect(() => {
    if (templates && templates.length > 0 && !selectedTemplate) {
      setSelectedTemplate(templates[0]);
      setMjmlContent(templates[0]?.content || '');
    }
  }, [templates, selectedTemplate]);

  const handleTemplateSelect = (template) => {
    if (hasChanges) {
      const confirmSwitch = window.confirm('You have unsaved changes. Switching templates will lose these changes. Continue?');
      if (!confirmSwitch) return;
    }
    setSelectedTemplate(template);
    setMjmlContent(template.content || '');
    setHasChanges(false);
  };

  const handleContentChange = (content) => {
    setMjmlContent(content);
    setHasChanges(true);
  };

  const handleSave = () => {
    if (!selectedTemplate) return;
    saveMutation.mutate({ id: selectedTemplate.id, content: mjmlContent });
  };

  const handleCreateTemplate = (formData) => {
    createMutation.mutate({
      ...formData,
      content: '<mjml><mj-body><mj-section><mj-column><mj-text>Your content here</mj-text></mj-column></mj-section></mj-body></mjml>',
    });
  };

  const handleDeleteTemplate = () => {
    if (!selectedTemplate) return;
    deleteMutation.mutate(selectedTemplate.id);
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
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-extrabold text-slate-900">Email Templates</h1>
          <p className="text-sm text-slate-500 mt-1">
            Manage notification templates for transactional emails sent to users.
          </p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
        >
          <span>+</span>
          Create Template
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
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
        <div className="lg:col-span-3">
          {selectedTemplate ? (
            <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h2 className="font-semibold text-slate-800">{selectedTemplate.name}</h2>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Subject: {selectedTemplate.subject}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setShowVariables(!showVariables)}
                    className={`px-3 py-2 text-sm font-medium rounded-lg transition-colors flex items-center gap-1 ${
                      showVariables
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    <span>§</span>
                    Variables
                  </button>
                  <button
                    onClick={() => setShowPreviewModal(true)}
                    className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-1"
                  >
                    <span>👁</span>
                    Preview
                  </button>
                  <button
                    onClick={() => setShowDeleteModal(true)}
                    className="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-1"
                  >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>

              {hasChanges && (
                <div className="mb-4 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2">
                  <span className="text-amber-600">●</span>
                  <span className="text-sm text-amber-700">You have unsaved changes</span>
                </div>
              )}

              {showVariables && (
                <div className="mb-4 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                  <h3 className="text-sm font-semibold text-slate-800 mb-2">Available Variables</h3>
                  <p className="text-xs text-slate-500 mb-3">Click to copy, then paste into your template.</p>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {templateVariables.map((item) => (
                      <button
                        key={item.variable}
                        onClick={() => {
                          navigator.clipboard.writeText(item.variable);
                          showToast('Copied!', `${item.variable} copied to clipboard.`, 'success');
                        }}
                        className="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-colors text-left"
                      >
                        <code className="text-xs font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                          {item.variable}
                        </code>
                        <span className="text-xs text-slate-500 truncate">{item.description}</span>
                      </button>
                    ))}
                  </div>
                </div>
              )}

              <TemplateEditor content={mjmlContent} onChange={handleContentChange} />

              <div className="mt-4 flex items-center justify-end gap-3">
                <button
                  onClick={handleSave}
                  disabled={!hasChanges || saveMutation.isPending}
                  title={!hasChanges ? 'No changes to save' : 'Save your changes'}
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors"
                >
                  {saveMutation.isPending ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
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

      <CreateTemplateModal
        isOpen={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        onSubmit={handleCreateTemplate}
        isLoading={createMutation.isPending}
      />

      <DeleteConfirmModal
        isOpen={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onConfirm={handleDeleteTemplate}
        templateName={selectedTemplate?.name}
        isLoading={deleteMutation.isPending}
      />

      <PreviewModal
        isOpen={showPreviewModal}
        onClose={() => setShowPreviewModal(false)}
        content={mjmlContent}
        templateName={selectedTemplate?.name}
      />
    </div>
  );
};

export default AdminEmailTemplateManagementPage;
