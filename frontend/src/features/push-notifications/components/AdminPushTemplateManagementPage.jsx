import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const fetchTemplates = async () => {
  const response = await api.get('/admin/push-templates');
  return response.data?.data || response.data || [];
};

const createTemplate = async (payload) => {
  const response = await api.post('/admin/push-templates', payload);
  return response.data?.data || response.data;
};

const updateTemplate = async ({ id, ...payload }) => {
  const response = await api.put(`/admin/push-templates/${id}`, payload);
  return response.data?.data || response.data;
};

const deleteTemplate = async (id) => {
  await api.delete(`/admin/push-templates/${id}`);
};

const templateTypes = ['order_confirmation', 'event_reminder', 'checkin_alert', 'promotional', 'custom'];

const typeLabels = {
  order_confirmation: 'Order Confirmation',
  event_reminder: 'Event Reminder',
  checkin_alert: 'Check-in Alert',
  promotional: 'Promotional',
  custom: 'Custom',
};

const TemplateModal = ({ isOpen, onClose, onSubmit, isLoading, initial }) => {
  const [form, setForm] = React.useState({
    name: initial?.name || '',
    type: initial?.type || 'order_confirmation',
    title: initial?.title || '',
    body: initial?.body || '',
  });
  const [errors, setErrors] = React.useState({});

  React.useEffect(() => {
    if (isOpen) {
      setForm({
        name: initial?.name || '',
        type: initial?.type || 'order_confirmation',
        title: initial?.title || '',
        body: initial?.body || '',
      });
      setErrors({});
    }
  }, [isOpen, initial]);

  if (!isOpen) return null;

  const set = (field) => (e) => {
    setForm((prev) => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const next = {};
    if (!form.name.trim()) next.name = 'Template name is required';
    if (!form.type.trim()) next.type = 'Type is required';
    if (!form.title.trim()) next.title = 'Notification title is required';
    if (!form.body.trim()) next.body = 'Notification body is required';
    setErrors(next);
    return Object.keys(next).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit({
      name: form.name.trim(),
      type: form.type.trim(),
      title: form.title.trim(),
      body: form.body.trim(),
      is_active: initial?.is_active ?? true,
    });
  };

  const inputClass = (field) =>
    `w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
      errors[field] ? 'border-red-300' : 'border-slate-200'
    }`;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div className="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-bold text-slate-900">
              {initial ? 'Edit Push Template' : 'Create Push Template'}
            </h2>
            <button onClick={onClose} className="text-slate-400 hover:text-slate-600 text-xl">×</button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Template Name</label>
              <input value={form.name} onChange={set('name')} placeholder="e.g. Order Confirmation" className={inputClass('name')} />
              {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Type</label>
              <select value={form.type} onChange={set('type')} className={inputClass('type')}>
                {templateTypes.map((t) => (
                  <option key={t} value={t}>{typeLabels[t] || t}</option>
                ))}
              </select>
              {errors.type && <p className="mt-1 text-xs text-red-600">{errors.type}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Title</label>
              <input value={form.title} onChange={set('title')} placeholder="e.g. Your tickets for {{event.title}} are here" className={inputClass('title')} />
              {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title}</p>}
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1">Body</label>
              <textarea value={form.body} onChange={set('body')} rows={4} placeholder="e.g. {{user.name}}, your tickets for {{event.date}} are ready to view." className={inputClass('body')} />
              {errors.body && <p className="mt-1 text-xs text-red-600">{errors.body}</p>}
            </div>

            <p className="text-xs text-slate-400 bg-slate-50 border border-slate-100 rounded-lg p-3">
              💡 Use <code className="font-mono text-indigo-600">{'{{variable}}'}</code> placeholders like{' '}
              <code className="font-mono text-indigo-600">{'{{user.name}}'}</code>,{' '}
              <code className="font-mono text-indigo-600">{'{{event.title}}'}</code>,{' '}
              <code className="font-mono text-indigo-600">{'{{event.date}}'}</code>.
            </p>

            <div className="flex justify-end gap-3 pt-2">
              <button type="button" onClick={onClose} className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Cancel
              </button>
              <button
                type="submit"
                disabled={isLoading}
                className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
              >
                {isLoading ? 'Saving…' : initial ? 'Save Changes' : 'Create Template'}
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
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div className="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <h2 className="text-lg font-bold text-slate-900">Delete Push Template</h2>
        <p className="mt-2 text-sm text-slate-500">
          Are you sure you want to delete <span className="font-semibold text-slate-700">“{templateName}”</span>?
          This cannot be undone.
        </p>
        <div className="mt-6 flex justify-end gap-3">
          <button onClick={onClose} className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600">Cancel</button>
          <button
            onClick={onConfirm}
            disabled={isLoading}
            className="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700 disabled:opacity-50"
          >
            {isLoading ? 'Deleting…' : 'Delete'}
          </button>
        </div>
      </div>
    </div>
  );
};

const AdminPushTemplateManagementPage = () => {
  const queryClient = useQueryClient();
  const { data: templates = [], isLoading, isError, error } = useQuery({
    queryKey: ['push-templates'],
    queryFn: fetchTemplates,
  });

  const [searchQuery, setSearchQuery] = React.useState('');
  const [showCreate, setShowCreate] = React.useState(false);
  const [editing, setEditing] = React.useState(null);
  const [deleting, setDeleting] = React.useState(null);

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['push-templates'] });

  const createMutation = useMutation({
    mutationFn: createTemplate,
    onSuccess: () => {
      invalidate();
      showToast('Template created', 'Push notification template created.', 'success');
      setShowCreate(false);
    },
    onError: (err) => showToast('Creation failed', err?.response?.data?.message || 'Failed to create template.', 'error'),
  });

  const updateMutation = useMutation({
    mutationFn: updateTemplate,
    onSuccess: () => {
      invalidate();
      showToast('Template saved', 'Push notification template updated.', 'success');
      setEditing(null);
    },
    onError: (err) => showToast('Save failed', err?.response?.data?.message || 'Failed to update template.', 'error'),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteTemplate,
    onSuccess: () => {
      invalidate();
      showToast('Template deleted', 'Push notification template deleted.', 'success');
      setDeleting(null);
    },
    onError: (err) => showToast('Deletion failed', err?.response?.data?.message || 'Failed to delete template.', 'error'),
  });

  const toggleActiveMutation = useMutation({
    mutationFn: ({ id, is_active }) => updateTemplate({ id, is_active: !is_active }),
    onSuccess: () => {
      invalidate();
    },
  });

  const filtered = searchQuery.trim()
    ? templates.filter((t) =>
        [t.name, t.type, t.title, t.body].some((v) => v && String(v).toLowerCase().includes(searchQuery.toLowerCase()))
      )
    : templates;

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton variant="text" className="h-6 w-56" />
        <Skeleton variant="card" className="h-16" />
        <Skeleton variant="card" className="h-16" />
        <Skeleton variant="card" className="h-16" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        {error?.response?.data?.message || 'Failed to load push templates. Please try again.'}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-extrabold text-slate-900">Push Notification Templates</h1>
          <p className="text-sm text-slate-500 mt-1">Manage templates used for push notifications.</p>
        </div>
        <button
          onClick={() => setShowCreate(true)}
          className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shrink-0"
        >
          + New Template
        </button>
      </div>

      <div className="relative">
        <input
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Search templates…"
          className="w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
      </div>

      {filtered.length === 0 ? (
        <div className="bg-white rounded-xl border border-slate-200 p-10 text-center shadow-sm">
          <div className="text-4xl mb-3">📱</div>
          <h3 className="font-bold text-slate-800">No push templates found</h3>
          <p className="text-sm text-slate-500 mt-1">
            {searchQuery.trim()
              ? 'Try a different search.'
              : 'Create your first push notification template to get started.'}
          </p>
          {!searchQuery.trim() && (
            <button onClick={() => setShowCreate(true)} className="mt-4 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
              Create your first template
            </button>
          )}
        </div>
      ) : (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100 overflow-hidden">
          {filtered.map((template) => (
            <div key={template.id} className="flex items-center justify-between gap-4 p-4">
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="font-semibold text-slate-800">{template.name}</span>
                  <span className="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full uppercase tracking-wide">
                    {typeLabels[template.type] || template.type}
                  </span>
                  <button
                    onClick={() => toggleActiveMutation.mutate({ id: template.id, is_active: template.isActive })}
                    className={`px-2 py-0.5 text-[10px] font-bold rounded-full ${
                      template.isActive
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-100 text-slate-400'
                    }`}
                    title="Click to toggle active state"
                  >
                    {template.isActive ? '● Active' : '○ Inactive'}
                  </button>
                </div>
                <p className="text-sm text-slate-600 mt-1 truncate">{template.title}</p>
                <p className="text-xs text-slate-400 mt-0.5 truncate">{template.body}</p>
              </div>
              <div className="flex items-center gap-2 shrink-0">
                <button
                  onClick={() => setEditing(template)}
                  className="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50"
                >
                  Edit
                </button>
                <button
                  onClick={() => setDeleting(template)}
                  className="px-3 py-1.5 rounded-lg border border-rose-200 text-xs font-medium text-rose-600 hover:bg-rose-50"
                >
                  Delete
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      <TemplateModal
        isOpen={showCreate}
        onClose={() => setShowCreate(false)}
        onSubmit={createMutation.mutate}
        isLoading={createMutation.isPending}
      />
      <TemplateModal
        isOpen={!!editing}
        initial={editing}
        onClose={() => setEditing(null)}
        onSubmit={(payload) => updateMutation.mutate({ id: editing.id, ...payload })}
        isLoading={updateMutation.isPending}
      />
      <DeleteConfirmModal
        isOpen={!!deleting}
        templateName={deleting?.name}
        onClose={() => setDeleting(null)}
        onConfirm={() => deleteMutation.mutate(deleting.id)}
        isLoading={deleteMutation.isPending}
      />
    </div>
  );
};

export default AdminPushTemplateManagementPage;
