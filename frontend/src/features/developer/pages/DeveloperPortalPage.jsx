import React, { useEffect, useState } from 'react';
import { api, showToast } from '../../../lib/api';

const TABS = [
  { id: 'api-keys', label: 'API Keys' },
  { id: 'webhooks', label: 'Webhooks' },
  { id: 'api-logs', label: 'API Logs' },
];

const EVENTS = [
  'order.created',
  'order.updated',
  'ticket.issued',
  'ticket.checked_in',
  'payment.succeeded',
  'payment.failed',
  'refund.processed',
  'event.created',
  'event.updated',
];

const SCOPES = [
  'events:read',
  'events:write',
  'orders:read',
  'orders:write',
  'tickets:read',
  'tickets:write',
];

const formatDate = (value) => {
  if (!value) return '—';
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString();
};

const EmptyState = ({ title, description, actionLabel, onAction }) => (
  <div className="py-12 text-center">
    <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">🛠️</div>
    <h3 className="text-lg font-bold text-slate-900">{title}</h3>
    <p className="mt-1 text-sm text-slate-500">{description}</p>
    {actionLabel && onAction && (
      <button
        type="button"
        onClick={onAction}
        className="mt-5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
      >
        {actionLabel}
      </button>
    )}
  </div>
);

const DeveloperPortalPage = () => {
  const [activeTab, setActiveTab] = useState('api-keys');
  const [loading, setLoading] = useState(true);

  const [keys, setKeys] = useState([]);
  const [webhooks, setWebhooks] = useState([]);
  const [logs, setLogs] = useState([]);

  const [creatingKeyOpen, setCreatingKeyOpen] = useState(false);
  const [creatingKey, setCreatingKey] = useState(false);
  const [newKeyName, setNewKeyName] = useState('');
  const [newKeyScopes, setNewKeyScopes] = useState(['events:read']);
  const [revealedKey, setRevealedKey] = useState(null);

  const [creatingWebhook, setCreatingWebhook] = useState(false);
  const [webhookUrl, setWebhookUrl] = useState('');
  const [webhookDescription, setWebhookDescription] = useState('');
  const [webhookEvents, setWebhookEvents] = useState(['order.created']);
  const [webhookSubmitting, setWebhookSubmitting] = useState(false);

  const loadKeys = async () => {
    try {
      const res = await api.get('/developer/api-keys');
      setKeys(res.data?.data || []);
    } catch (err) {
      showToast('Unable to load API keys', err?.response?.data?.message || 'Something went wrong.', 'warning');
    }
  };

  const loadWebhooks = async () => {
    try {
      const res = await api.get('/developer/webhooks');
      setWebhooks(res.data?.data || []);
    } catch (err) {
      showToast('Unable to load webhooks', err?.response?.data?.message || 'Something went wrong.', 'warning');
    }
  };

  const loadLogs = async () => {
    try {
      const res = await api.get('/developer/api-logs');
      setLogs(res.data?.data || []);
    } catch (err) {
      showToast('Unable to load API logs', err?.response?.data?.message || 'Something went wrong.', 'warning');
    }
  };

  const loadActiveTab = async () => {
    setLoading(true);
    try {
      if (activeTab === 'api-keys') await loadKeys();
      if (activeTab === 'webhooks') await loadWebhooks();
      if (activeTab === 'api-logs') await loadLogs();
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadActiveTab();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeTab]);

  const submitCreateKey = async (e) => {
    e.preventDefault();
    if (!newKeyName.trim()) {
      showToast('Name required', 'Give this key a name so you can recognize it later.', 'warning');
      return;
    }
    setCreatingKey(true);
    try {
      const res = await api.post('/developer/api-keys', {
        name: newKeyName.trim(),
        scopes: newKeyScopes,
      });
      setRevealedKey({ name: newKeyName.trim(), rawKey: res.data.raw_key, key: res.data.api_key });
      setNewKeyName('');
      setNewKeyScopes(['events:read']);
      setCreatingKey(false);
      setCreatingKeyOpen(false);
      await loadKeys();
      showToast('API key created', 'Copy the key now — it will not be shown again.', 'success');
    } catch (err) {
      setCreatingKey(false);
      showToast('Create failed', err?.response?.data?.message || 'Could not create the API key.', 'error');
    }
  };

  const revokeKey = async (key) => {
    if (!window.confirm(`Revoke API key "${key.name}"? Requests using it will stop working.`)) return;
    try {
      await api.delete(`/developer/api-keys/${key.id}`);
      showToast('Key revoked', `"${key.name}" has been revoked.`, 'success');
      await loadKeys();
    } catch (err) {
      showToast('Revoke failed', err?.response?.data?.message || 'Could not revoke the key.', 'error');
    }
  };

  const copyRawKey = async (raw) => {
    try {
      await navigator.clipboard.writeText(raw);
      showToast('Copied', 'The API key was copied to your clipboard.', 'success');
    } catch {
      showToast('Copy failed', 'Your browser blocked clipboard access. Copy the key manually.', 'warning');
    }
  };

  const toggleScope = (scope) => {
    setNewKeyScopes((prev) =>
      prev.includes(scope) ? prev.filter((s) => s !== scope) : [...prev, scope]
    );
  };

  const toggleWebhookEvent = (eventName) => {
    setWebhookEvents((prev) =>
      prev.includes(eventName) ? prev.filter((s) => s !== eventName) : [...prev, eventName]
    );
  };

  const submitCreateWebhook = async (e) => {
    e.preventDefault();
    if (!webhookUrl.trim()) {
      showToast('URL required', 'Enter the endpoint URL that should receive events.', 'warning');
      return;
    }
    if (webhookEvents.length === 0) {
      showToast('Select an event', 'Choose at least one event to subscribe to.', 'warning');
      return;
    }
    setWebhookSubmitting(true);
    try {
      await api.post('/developer/webhooks', {
        url: webhookUrl.trim(),
        description: webhookDescription.trim() || null,
        subscribedEvents: webhookEvents,
      });
      setWebhookUrl('');
      setWebhookDescription('');
      setWebhookEvents(['order.created']);
      setCreatingWebhook(false);
      await loadWebhooks();
      showToast('Webhook created', 'Your endpoint is now subscribed to the selected events.', 'success');
    } catch (err) {
      const msg =
        err?.response?.data?.errors?.subscribedEvents?.[0] ||
        err?.response?.data?.message ||
        'Could not create the webhook.';
      showToast('Create failed', msg, 'error');
    } finally {
      setWebhookSubmitting(false);
    }
  };

  const deleteWebhook = async (webhook) => {
    if (!window.confirm(`Delete webhook ${webhook.url}?`)) return;
    try {
      await api.delete(`/developer/webhooks/${webhook.id}`);
      showToast('Webhook deleted', 'Your endpoint will stop receiving events.', 'success');
      await loadWebhooks();
    } catch (err) {
      showToast('Delete failed', err?.response?.data?.message || 'Could not delete the webhook.', 'error');
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-5xl">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Developer Portal</h1>
          <p className="mt-2 text-sm text-slate-500">
            Manage API access and integrations for your organizer account.
          </p>
        </div>

        <div className="mb-6 flex gap-2 border-b border-slate-200">
          {TABS.map((tab) => (
            <button
              key={tab.id}
              type="button"
              onClick={() => setActiveTab(tab.id)}
              className={`px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition-colors ${
                activeTab === tab.id
                  ? 'border-indigo-600 text-indigo-700'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {loading ? (
          <div className="flex items-center justify-center min-h-[40vh]">
            <div className="h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
          </div>
        ) : (
          <div>
            {activeTab === 'api-keys' && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-100 p-5">
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">API Keys</h2>
                    <p className="text-sm text-slate-500">Personal access tokens for the public API.</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => setCreatingKeyOpen(true)}
                    className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
                  >
                    + Create API key
                  </button>
                </div>
                {keys.length === 0 ? (
                  <EmptyState
                    title="No API keys yet"
                    description="Create your first key to start building with the Eventiq API."
                    actionLabel="Create your first API key"
                    onAction={() => setCreatingKeyOpen(true)}
                  />
                ) : (
                  <div className="divide-y divide-slate-100">
                    {keys.map((key) => (
                      <div key={key.id} className="flex items-center justify-between gap-4 p-5">
                        <div>
                          <div className="flex items-center gap-3">
                            <span className="font-semibold text-slate-900">{key.name}</span>
                            <span className="font-mono text-xs text-slate-400">{key.key_prefix}••••••</span>
                            {key.revoked_at && (
                              <span className="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                Revoked
                              </span>
                            )}
                          </div>
                          <div className="mt-1 flex flex-wrap gap-1.5">
                            {(key.scopes || []).map((scope) => (
                              <span key={scope} className="px-2 py-0.5 bg-indigo-50 text-indigo-600 text-xs font-medium rounded-full">
                                {scope}
                              </span>
                            ))}
                          </div>
                          <p className="mt-1 text-xs text-slate-400">Created {formatDate(key.created_at)}</p>
                        </div>
                        <button
                          type="button"
                          onClick={() => revokeKey(key)}
                          disabled={Boolean(key.revoked_at)}
                          className="px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          Revoke
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'webhooks' && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-100 p-5">
                  <div>
                    <h2 className="text-lg font-bold text-slate-900">Webhooks</h2>
                    <p className="text-sm text-slate-500">Receive real-time event notifications at your endpoints.</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => setCreatingWebhook(true)}
                    className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
                  >
                    + Add webhook
                  </button>
                </div>
                {webhooks.length === 0 ? (
                  <EmptyState
                    title="No webhooks yet"
                    description="Register an endpoint to start receiving real-time events."
                    actionLabel="Add your first webhook"
                    onAction={() => setCreatingWebhook(true)}
                  />
                ) : (
                  <div className="divide-y divide-slate-100">
                    {webhooks.map((webhook) => (
                      <div key={webhook.id} className="flex items-center justify-between gap-4 p-5">
                        <div className="min-w-0">
                          <div className="flex items-center gap-3">
                            <span className="font-mono text-sm text-slate-700 truncate">{webhook.url}</span>
                            <span
                              className={`px-2 py-0.5 text-xs font-semibold rounded-full ${
                                webhook.status === 'active'
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : 'bg-red-100 text-red-700'
                              }`}
                            >
                              {webhook.status}
                            </span>
                          </div>
                          {webhook.description && (
                            <p className="mt-1 text-xs text-slate-500">{webhook.description}</p>
                          )}
                          <div className="mt-1 flex flex-wrap gap-1.5">
                            {(webhook.subscribed_events || []).map((eventName) => (
                              <span key={eventName} className="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-medium rounded-full">
                                {eventName}
                              </span>
                            ))}
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => deleteWebhook(webhook)}
                          className="px-3 py-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        >
                          Delete
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'api-logs' && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="border-b border-slate-100 p-5">
                  <h2 className="text-lg font-bold text-slate-900">API Logs</h2>
                  <p className="text-sm text-slate-500">Recent activity across your developer integrations.</p>
                </div>
                {logs.length === 0 ? (
                  <EmptyState
                    title="No API activity yet"
                    description="Once your integrations call the API, activity will appear here."
                  />
                ) : (
                  <div className="divide-y divide-slate-100">
                    {logs.map((logEntry) => (
                      <div key={logEntry.id} className="flex items-center justify-between gap-4 p-5">
                        <div>
                          <div className="flex items-center gap-3">
                            <span className="font-semibold text-slate-900">{logEntry.action}</span>
                            <span
                              className={`px-2 py-0.5 text-xs font-semibold rounded-full ${
                                logEntry.status === 'success'
                                  ? 'bg-emerald-100 text-emerald-700'
                                  : 'bg-red-100 text-red-700'
                              }`}
                            >
                              {logEntry.status}
                            </span>
                          </div>
                          <p className="mt-1 text-xs text-slate-500">
                            {logEntry.path || logEntry.source || 'developer_portal'} · {logEntry.ipAddress || '—'}
                          </p>
                        </div>
                        <span className="text-xs text-slate-400">{formatDate(logEntry.createdAt)}</span>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>

      {creatingKeyOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" role="dialog" aria-modal="true">
          <form onSubmit={submitCreateKey} className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-lg font-bold text-slate-900">Create API key</h3>
            <p className="mt-1 text-sm text-slate-500">The full key is shown only once after creation.</p>
            <label className="mt-4 block text-sm font-semibold text-slate-700">
              Name
              <input
                type="text"
                value={newKeyName}
                onChange={(e) => setNewKeyName(e.target.value)}
                placeholder="e.g. Production server"
                className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              />
            </label>
            <div className="mt-4">
              <span className="text-sm font-semibold text-slate-700">Scopes</span>
              <div className="mt-2 grid grid-cols-2 gap-2">
                {SCOPES.map((scope) => (
                  <label key={scope} className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                      type="checkbox"
                      checked={newKeyScopes.includes(scope)}
                      onChange={() => toggleScope(scope)}
                    />
                    <span className="font-mono">{scope}</span>
                  </label>
                ))}
              </div>
            </div>
            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setCreatingKeyOpen(false)}
                className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={creatingKey}
                className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
              >
                {creatingKey ? 'Creating…' : 'Create key'}
              </button>
            </div>
          </form>
        </div>
      )}

      {revealedKey && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" role="alertdialog" aria-modal="true">
          <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-lg font-bold text-slate-900">Your API key</h3>
            <p className="mt-1 text-sm text-slate-500">
              This is the only time the full key is shown. Store it securely now.
            </p>
            <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
              <code className="break-all font-mono text-sm text-slate-800">{revealedKey.rawKey}</code>
            </div>
            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => copyRawKey(revealedKey.rawKey)}
                className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50"
              >
                Copy key
              </button>
              <button
                type="button"
                onClick={() => setRevealedKey(null)}
                className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
              >
                Done
              </button>
            </div>
          </div>
        </div>
      )}

      {creatingWebhook && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" role="dialog" aria-modal="true">
          <form onSubmit={submitCreateWebhook} className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 className="text-lg font-bold text-slate-900">Add webhook</h3>
            <label className="mt-4 block text-sm font-semibold text-slate-700">
              Endpoint URL
              <input
                type="url"
                value={webhookUrl}
                onChange={(e) => setWebhookUrl(e.target.value)}
                placeholder="https://example.com/hooks/eventiq"
                className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              />
            </label>
            <label className="mt-4 block text-sm font-semibold text-slate-700">
              Description (optional)
              <input
                type="text"
                value={webhookDescription}
                onChange={(e) => setWebhookDescription(e.target.value)}
                placeholder="e.g. Production order alerts"
                className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
              />
            </label>
            <div className="mt-4">
              <span className="text-sm font-semibold text-slate-700">Subscribe to events</span>
              <div className="mt-2 grid grid-cols-2 gap-2">
                {EVENTS.map((eventName) => (
                  <label key={eventName} className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                      type="checkbox"
                      checked={webhookEvents.includes(eventName)}
                      onChange={() => toggleWebhookEvent(eventName)}
                    />
                    <span className="font-mono text-xs">{eventName}</span>
                  </label>
                ))}
              </div>
            </div>
            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setCreatingWebhook(false)}
                className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={webhookSubmitting}
                className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
              >
                {webhookSubmitting ? 'Creating…' : 'Create webhook'}
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
};

export default DeveloperPortalPage;