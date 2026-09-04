import React from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api, showToast } from '../../../lib/api';
import { useEmailTemplates } from '../hooks/useEmailTemplates';
import TemplateEditor from '../components/TemplateEditor';
import Skeleton from '../../../components/Skeleton';

const saveEmailTemplate = async ({ id, content }) => {
  const response = await api.put(`/email-templates/${id}`, { content });
  return response.data?.data || response.data;
};

const createEmailTemplate = async ({ name, subject, key, content }) => {
  const response = await api.post('/email-templates', { name, subject, key, content });
  return response.data?.data || response.data;
};

const deleteEmailTemplate = async (id) => {
  await api.delete(`/email-templates/${id}`);
};

const sendTestEmail = async ({ id, email }) => {
  const response = await api.post(`/email-templates/${id}/send-test`, { email });
  return response.data;
};

const duplicateEmailTemplate = async ({ id, name, subject, key, content }) => {
  const response = await api.post('/email-templates', { name, subject, key, content });
  return response.data?.data || response.data;
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

const UNDO_DURATION = 5000;

const DEFAULT_TEMPLATES = [
  {
    name: 'Order Confirmation',
    subject: 'Your order #{{order.id}} is confirmed',
    key: 'order_confirmation',
    content: `<mjml>
  <mj-head>
    <mj-title>Order Confirmation</mj-title>
    <mj-style>
      .btn { background: #6366f1; color: #ffffff; }
    </mj-style>
  </mj-head>
  <mj-body>
    <mj-section background-color="#f8fafc" padding="40px 20px">
      <mj-column>
        <mj-text font-size="24px" font-weight="700" color="#0f172a" text-align="center">Order Confirmed 🎉</mj-text>
        <mj-text font-size="16px" color="#475569" text-align="center" padding-top="8px">
          Hi {{user.name}}, your order #{{order.id}} has been received!
        </mj-text>
        <mj-text font-size="14px" color="#64748b" text-align="center" padding-top="16px">
          Total: {{order.total}}<br/>
          Ticket: {{ticket.code}}
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>`,
  },
  {
    name: 'Event Reminder',
    subject: 'Reminder: {{event.title}} is tomorrow',
    key: 'event_reminder',
    content: `<mjml>
  <mj-head>
    <mj-title>Event Reminder</mj-title>
  </mj-head>
  <mj-body>
    <mj-section background-color="#fefce8" padding="40px 20px">
      <mj-column>
        <mj-text font-size="24px" font-weight="700" color="#0f172a" text-align="center">Don't forget! ⏰</mj-text>
        <mj-text font-size="16px" color="#475569" text-align="center" padding-top="8px">
          {{event.title}} is tomorrow at {{event.date}}.
        </mj-text>
        <mj-text font-size="14px" color="#64748b" text-align="center" padding-top="8px">
          Venue: {{event.venue}}
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>`,
  },
  {
    name: 'Welcome Email',
    subject: 'Welcome to EventIQ, {{user.name}}!',
    key: 'welcome_email',
    content: `<mjml>
  <mj-head>
    <mj-title>Welcome to EventIQ</mj-title>
  </mj-head>
  <mj-body>
    <mj-section background-color="#ecfdf5" padding="40px 20px">
      <mj-column>
        <mj-text font-size="24px" font-weight="700" color="#0f172a" text-align="center">Welcome aboard! 👋</mj-text>
        <mj-text font-size="16px" color="#475569" text-align="center" padding-top="8px">
          Hi {{user.name}}, you're all set to start exploring events.
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>`,
  },
];

const seedDefaultTemplates = async () => {
  const results = [];
  for (const t of DEFAULT_TEMPLATES) {
    const response = await api.post('/email-templates', t);
    results.push(response.data?.data || response.data);
  }
  return results;
};

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
    else if (formData.key.length > 50) newErrors.key = 'Key must be 50 characters or fewer';
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

const SendTestModal = ({ isOpen, onClose, onSend, templateName, isLoading }) => {
  const [email, setEmail] = React.useState('');
  const [error, setError] = React.useState('');

  if (!isOpen) return null;

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!email.trim()) {
      setError('Email address is required');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      setError('Please enter a valid email address');
      return;
    }
    setError('');
    onSend(email.trim());
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div className="p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="p-2 bg-indigo-100 rounded-full">
              <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>
            <div>
              <h3 className="font-semibold text-slate-900">Send Test Email</h3>
              <p className="text-sm text-slate-500">Verify how "{templateName}" renders in a real inbox.</p>
              <p className="text-xs text-slate-500 mt-1">
                Enter a user's email address to send a test email. This helps verify your template
                renders correctly before sending to your actual users.
              </p>
            </div>
          </div>
          <form onSubmit={handleSubmit}>
            <div className="mb-4">
              <label className="block text-sm font-medium text-slate-700 mb-1">
                Recipient email address
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => { setEmail(e.target.value); setError(''); }}
                placeholder="test@example.com"
                autoFocus
                className={`w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
                  error ? 'border-red-300' : 'border-slate-200'
                }`}
              />
              {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
              <p className="mt-1.5 text-xs text-slate-500">
                The template will be rendered with sample data and sent to this address.
              </p>
            </div>
            <div className="flex items-center justify-end gap-3">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white text-sm font-medium rounded-lg transition-colors"
              >
                {isLoading ? 'Sending...' : 'Send Test Email'}
              </button>
            </div>
          </form>
        </div>
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

const SeedConfirmModal = ({ isOpen, onClose, onConfirm, isLoading }) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <div className="relative bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
        <div className="p-4">
          <div className="flex items-center gap-3 mb-4">
            <div className="p-2 bg-amber-100 rounded-full">
              <svg className="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm.008-6.905a9 9 0 11-2.25-.241.75.75 0 00.626.825 1.5 1.5 0 10-1.5-1.5A1.5 1.5 0 0112 15z" />
              </svg>
            </div>
            <div>
              <h3 className="font-semibold text-slate-900">Create Default Templates</h3>
              <p className="text-sm text-slate-500">This will create default email templates.</p>
            </div>
          </div>
          <p className="text-sm text-slate-600 mb-4">
            This action creates Order Confirmation, Event Reminder, and Welcome Email templates. If templates with the same keys already exist, they may be overwritten.
          </p>
          <div className="flex gap-2">
            <button
              onClick={onClose}
              disabled={isLoading}
              className="flex-1 px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={onConfirm}
              disabled={isLoading}
              className="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg text-sm font-medium transition-colors"
            >
              {isLoading ? 'Creating...' : 'Create Templates'}
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

const UndoSnackbar = ({ message, onUndo, onDismiss, duration = UNDO_DURATION }) => {
  const [progress, setProgress] = React.useState(100);

  React.useEffect(() => {
    const startTime = Date.now();
    const interval = setInterval(() => {
      const elapsed = Date.now() - startTime;
      const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
      setProgress(remaining);
      if (remaining === 0) {
        clearInterval(interval);
        onDismiss();
      }
    }, 50);
    return () => clearInterval(interval);
  }, [duration, onDismiss]);

  return (
    <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white rounded-lg shadow-lg overflow-hidden">
      <div className="flex items-center gap-4 px-4 py-3">
        <span className="text-sm">{message}</span>
        <button
          onClick={onUndo}
          className="text-sm font-medium text-indigo-300 hover:text-indigo-200 transition-colors"
        >
          UNDO
        </button>
      </div>
      <div className="h-1 bg-slate-700">
        <div
          className="h-full bg-indigo-500 transition-all duration-50"
          style={{ width: `${progress}%` }}
        />
      </div>
    </div>
  );
};

const AdminEmailTemplateManagementPage = () => {
  const queryClient = useQueryClient();
  const { data: templates, isLoading, isError, error } = useEmailTemplates();

  const [selectedTemplate, setSelectedTemplate] = React.useState(null);
  const [mjmlContent, setMjmlContent] = React.useState('');
  const [hasChanges, setHasChanges] = React.useState(false);
  const [showCreateModal, setShowCreateModal] = React.useState(false);
  const [showDeleteModal, setShowDeleteModal] = React.useState(false);
  const [showPreviewModal, setShowPreviewModal] = React.useState(false);
  const [showSendTestModal, setShowSendTestModal] = React.useState(false);
  const [searchQuery, setSearchQuery] = React.useState('');
  const [deletedTemplate, setDeletedTemplate] = React.useState(null);
  const [showUndo, setShowUndo] = React.useState(false);
  const [showSeedModal, setShowSeedModal] = React.useState(false);

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
    onSuccess: (_, deletedId) => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      setSelectedTemplate(null);
      setMjmlContent('');
      setHasChanges(false);
      setShowDeleteModal(false);
      setShowUndo(true);
    },
    onError: (err) => {
      showToast('Deletion failed', err?.message || 'Failed to delete template. Please try again.', 'error');
    },
  });

  const duplicateMutation = useMutation({
    mutationFn: duplicateEmailTemplate,
    onSuccess: (newTemplate) => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Template duplicated', `Created copy as "${newTemplate.name}".`, 'success');
      setSelectedTemplate(newTemplate);
      setMjmlContent(newTemplate.content || '');
      setHasChanges(false);
    },
    onError: (err) => {
      showToast('Duplication failed', err?.message || 'Failed to duplicate template. Please try again.', 'error');
    },
  });

  const undoDeleteMutation = useMutation({
    mutationFn: createEmailTemplate,
    onSuccess: (restoredTemplate) => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Template restored', `"${restoredTemplate.name}" has been restored.`, 'success');
      setSelectedTemplate(restoredTemplate);
      setMjmlContent(restoredTemplate.content || '');
      setDeletedTemplate(null);
      setShowUndo(false);
    },
    onError: () => {
      showToast('Restore failed', 'Failed to restore template. Please try again.', 'error');
    },
  });

  const sendTestMutation = useMutation({
    mutationFn: sendTestEmail,
    onSuccess: () => {
      showToast(
        'Test email sent',
        `A test email has been sent to the provided address.`,
        'success'
      );
      setShowSendTestModal(false);
    },
    onError: (err) => {
      const status = err?.response?.status;
      const detail = status === 500
        ? 'Mail server is not configured. Contact your platform administrator.'
        : status === 422
          ? 'The email server rejected the request. Check your mail configuration.'
          : (err?.message || 'Failed to send test email. Please try again.');
      showToast(
        'Test email failed',
        detail,
        'error'
      );
    },
  });

  const seedMutation = useMutation({
    mutationFn: seedDefaultTemplates,
    onSuccess: (created) => {
      queryClient.invalidateQueries({ queryKey: ['email-templates'] });
      showToast('Templates created', `${created.length} default templates created.`, 'success');
      if (created[0]) {
        setSelectedTemplate(created[0]);
        setMjmlContent(created[0].content || '');
      }
    },
    onError: (err) => {
      showToast('Creation failed', err?.message || 'Failed to create default templates.', 'error');
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
    setDeletedTemplate(selectedTemplate);
    deleteMutation.mutate(selectedTemplate.id);
  };

  const handleDuplicateTemplate = () => {
    if (!selectedTemplate) return;
    const newKey = `${selectedTemplate.key}_copy`;
    const newName = `${selectedTemplate.name} (Copy)`;
    duplicateMutation.mutate({
      id: selectedTemplate.id,
      name: newName,
      subject: selectedTemplate.subject,
      key: newKey,
      content: selectedTemplate.content,
    });
  };

  const handleUndoDelete = () => {
    if (!deletedTemplate) return;
    undoDeleteMutation.mutate({
      name: deletedTemplate.name,
      subject: deletedTemplate.subject,
      key: deletedTemplate.key,
      content: deletedTemplate.content,
    });
  };

  const filteredTemplates = React.useMemo(() => {
    if (!templates) return [];
    if (!searchQuery.trim()) return templates;
    const query = searchQuery.toLowerCase();
    return templates.filter(
      (t) =>
        t.name?.toLowerCase().includes(query) ||
        t.key?.toLowerCase().includes(query) ||
        t.subject?.toLowerCase().includes(query)
    );
  }, [templates, searchQuery]);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton variant="text" className="h-8 w-64" />
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
      <div className="space-y-6">
        <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
          <h3 className="font-bold">Failed to load email templates</h3>
          <p className="text-sm mt-1">{error?.message || 'An error occurred while fetching templates.'}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
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
              <p className="text-xs text-slate-500 mt-0.5">{filteredTemplates.length} of {templates?.length || 0} templates</p>
            </div>
            <div className="p-3 border-b border-slate-100">
              <div className="relative">
                <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search templates..."
                  className="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                {searchQuery && (
                  <button
                    onClick={() => setSearchQuery('')}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                  >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                )}
              </div>
            </div>
            <div className="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
              {filteredTemplates.length > 0 ? (
                filteredTemplates.map((template) => (
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
                  {searchQuery ? 'No templates match your search.' : 'No templates found. Create your first email template.'}
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
                  <div className="flex items-center gap-2">
                    <h2 className="font-semibold text-slate-800">{selectedTemplate.name}</h2>
                    <span className={`text-[10px] font-medium px-2 py-0.5 rounded-full ${
                      selectedTemplate.status === 'active'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-100 text-slate-600'
                    }`}>
                      {selectedTemplate.status === 'active' ? 'Active' : 'Draft'}
                    </span>
                    <span className="text-xs font-mono text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                      {selectedTemplate.key}
                    </span>
                  </div>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Subject: {selectedTemplate.subject}
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <button
                    onClick={handleDuplicateTemplate}
                    disabled={duplicateMutation.isPending}
                    className="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-1"
                    title="Duplicate this template"
                  >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    {duplicateMutation.isPending ? 'Copying...' : 'Duplicate'}
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

              <div className="mb-4 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="text-sm font-semibold text-slate-800">Available Variables</h3>
                  <span className="text-xs text-slate-500">Click to copy</span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {templateVariables.map((item) => (
                    <button
                      key={item.variable}
                      type="button"
                      onClick={() => {
                        navigator.clipboard.writeText(item.variable);
                        showToast('Copied!', `${item.variable} copied to clipboard.`, 'success');
                      }}
                      className="group flex items-center justify-between p-2 bg-white border border-slate-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50/50 focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:outline-none transition-colors text-left"
                      title={`Click to copy ${item.variable}`}
                      aria-label={`Copy variable ${item.variable}: ${item.description}`}
                    >
                      <div className="flex items-center gap-2 min-w-0">
                        <code className="text-xs font-mono text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded shrink-0">
                          {item.variable}
                        </code>
                        <span className="text-xs text-slate-500 truncate">{item.description}</span>
                      </div>
                      <span className="text-xs text-slate-400 group-hover:text-indigo-600 shrink-0 ml-1 select-none" aria-hidden="true">📋</span>
                    </button>
                  ))}
                </div>
              </div>

              <TemplateEditor content={mjmlContent} onChange={handleContentChange} />

              <div className="mt-4 flex items-center justify-end gap-3">
                <button
                  onClick={() => setShowSendTestModal(true)}
                  disabled={!selectedTemplate}
                  title={!selectedTemplate ? 'Select a template first' : 'Send a test email to verify this template renders correctly'}
                  className="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-sm font-medium rounded-lg transition-colors"
                >
                  Send Test Email
                </button>
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
              <span className="text-4xl mb-2 inline-block text-indigo-600">✉️</span>
              <h3 className="font-semibold text-slate-800">No templates yet</h3>
              <p className="text-sm text-slate-500 mb-4 max-w-xl mx-auto">
                Get started by creating your first email template. Email templates power automated, branded notifications sent to users for key platform events like event registrations, ticket confirmations, reminders, and welcome messages.
              <div className="space-y-3">
                                <button
                  onClick={() => setShowSeedModal(true)}
                  disabled={seedMutation.isPending}
                  className="w-full flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-lg text-sm font-medium transition-colors"
                >
                  {seedMutation.isPending ? 'Creating...' : '✨ Create Default Templates'}
                </button>
                <p className="text-xs text-slate-500 text-center">
                  Instantly create Order Confirmation, Event Reminder, and Welcome Email templates.
                </p>
                <div className="pt-2 border-t border-slate-100">
                  <button
                    onClick={() => setShowCreateModal(true)}
                    className="w-full flex items-center justify-center gap-2 px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    Create Custom Template
                  </button>
                </div>
              </div>
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

      <SeedConfirmModal
        isOpen={showSeedModal}
        onClose={() => setShowSeedModal(false)}
        onConfirm={() => seedMutation.mutate()}
        isLoading={seedMutation.isPending}
      />

      <PreviewModal
        isOpen={showPreviewModal}
        onClose={() => setShowPreviewModal(false)}
        content={mjmlContent}
        templateName={selectedTemplate?.name}
      />

      <SendTestModal
        isOpen={showSendTestModal}
        onClose={() => setShowSendTestModal(false)}
        onSend={(email) =>
          sendTestMutation.mutate({ id: selectedTemplate.id, email })
        }
        templateName={selectedTemplate?.name}
        isLoading={sendTestMutation.isPending}
      />

      {showUndo && deletedTemplate && (
        <UndoSnackbar
          message={`"${deletedTemplate.name}" deleted`}
          onUndo={handleUndoDelete}
          onDismiss={() => {
            setShowUndo(false);
            setDeletedTemplate(null);
          }}
        />
      )}
    </div>
  );
};

export default AdminEmailTemplateManagementPage;
