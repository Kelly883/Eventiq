import React, { useEffect, useMemo, useState } from 'react';
import { useApiKeyStore } from '../stores/apiKeyStore';

export default function DeveloperPortalPage() {
  const { apiKeys, scopes, rawKey, isLoading, error, load, createKey, revokeKey, clearRawKey } = useApiKeyStore();
  const [name, setName] = useState('');
  const [selectedScopes, setSelectedScopes] = useState(['events:read']);
  const [expiresAt, setExpiresAt] = useState('');

  useEffect(() => {
    load();
  }, [load]);

  const scopeOptions = useMemo(() => Object.entries(scopes), [scopes]);

  const toggleScope = (scope) => {
    setSelectedScopes((current) => (
      current.includes(scope)
        ? current.filter((item) => item !== scope)
        : [...current, scope]
    ));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    await createKey({
      name,
      scopes: selectedScopes,
      expires_at: expiresAt || null,
    });
    setName('');
    setSelectedScopes(['events:read']);
    setExpiresAt('');
  };

  return (
    <section className="mx-auto max-w-6xl px-4 py-10 text-left">
      <div className="mb-8 rounded-3xl bg-slate-900 p-8 text-white shadow-xl">
        <p className="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-200">Developer Portal</p>
        <h1 className="mt-3 text-3xl font-black tracking-tight">API keys</h1>
        <p className="mt-3 max-w-3xl text-slate-200">
          Create scoped keys for integrations. Raw keys are shown once, then only their bcrypt hash is retained by Eventiq.
        </p>
      </div>

      {rawKey && (
        <div className="mb-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2 className="text-lg font-bold">Copy this key now</h2>
              <p className="mt-1 text-sm">This raw API key will not be shown again after you dismiss it.</p>
              <code className="mt-3 block break-all rounded-xl bg-white p-3 text-sm font-semibold shadow-inner">{rawKey}</code>
            </div>
            <button className="rounded-lg bg-amber-200 px-3 py-2 text-sm font-bold" onClick={clearRawKey} type="button">
              Dismiss
            </button>
          </div>
        </div>
      )}

      {error && <div className="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">{error}</div>}

      <div className="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <form className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" onSubmit={handleSubmit}>
          <h2 className="text-xl font-black text-slate-900">Create API key</h2>
          <label className="mt-5 block text-sm font-bold text-slate-700" htmlFor="api-key-name">Name</label>
          <input
            className="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2"
            id="api-key-name"
            maxLength={120}
            onChange={(event) => setName(event.target.value)}
            placeholder="Production integration"
            required
            value={name}
          />

          <label className="mt-5 block text-sm font-bold text-slate-700" htmlFor="api-key-expiry">Expires at (optional)</label>
          <input
            className="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2"
            id="api-key-expiry"
            onChange={(event) => setExpiresAt(event.target.value)}
            type="datetime-local"
            value={expiresAt}
          />

          <fieldset className="mt-5">
            <legend className="text-sm font-bold text-slate-700">Scopes</legend>
            <div className="mt-2 space-y-2">
              {scopeOptions.map(([scope, description]) => (
                <label className="flex items-start gap-3 rounded-xl border border-slate-200 p-3" key={scope}>
                  <input
                    checked={selectedScopes.includes(scope)}
                    className="mt-1"
                    onChange={() => toggleScope(scope)}
                    type="checkbox"
                  />
                  <span>
                    <span className="block font-mono text-sm font-bold text-slate-900">{scope}</span>
                    <span className="block text-sm text-slate-600">{description}</span>
                  </span>
                </label>
              ))}
            </div>
          </fieldset>

          <button
            className="mt-6 w-full rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-400"
            disabled={isLoading || selectedScopes.length === 0}
            type="submit"
          >
            {isLoading ? 'Working…' : 'Create key'}
          </button>
        </form>

        <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 className="text-xl font-black text-slate-900">Existing keys</h2>
          <div className="mt-4 divide-y divide-slate-100">
            {apiKeys.length === 0 && <p className="py-8 text-center text-slate-500">No API keys yet.</p>}
            {apiKeys.map((apiKey) => (
              <article className="py-4" key={apiKey.id}>
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <h3 className="font-bold text-slate-900">{apiKey.name}</h3>
                    <p className="mt-1 font-mono text-xs text-slate-500">prefix: {apiKey.key_prefix}</p>
                    <div className="mt-2 flex flex-wrap gap-2">
                      {apiKey.scopes.map((scope) => (
                        <span className="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700" key={scope}>{scope}</span>
                      ))}
                    </div>
                    <p className="mt-2 text-xs text-slate-500">
                      Last used: {apiKey.last_used_at ?? 'never'} · Expires: {apiKey.expires_at ?? 'never'}
                    </p>
                  </div>
                  {apiKey.revoked_at ? (
                    <span className="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">Revoked</span>
                  ) : (
                    <button
                      className="rounded-lg border border-red-200 px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50"
                      disabled={isLoading}
                      onClick={() => revokeKey(apiKey.id)}
                      type="button"
                    >
                      Revoke
                    </button>
                  )}
                </div>
              </article>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
