import React, { useState } from 'react';
import { useOfflineSync } from '../../offline/hooks/useOfflineSync';
import { useDeviceToken } from '../../offline/hooks/useDeviceToken';
import { useRotateDeviceToken } from '../../offline/hooks/useRotateDeviceToken';
import { showToast } from '../../../lib/api';

const DeviceLocalizationSyncPage = () => {
  const { token: deviceToken, regenerate: regenerateDeviceToken } = useDeviceToken();
  const { loading, error, tickets, lastSyncedAt, syncVersion, sync } = useOfflineSync();
  const { rotate: rotateDeviceToken } = useRotateDeviceToken();
  const [rotating, setRotating] = useState(false);

  const handleRotate = async () => {
    setRotating(true);
    try {
      const newToken = await rotateDeviceToken();
      await regenerateDeviceToken();
      showToast('Device token rotated', '', 'success');
    } catch (e) {
      const message = e instanceof Error ? e.message : 'Failed to rotate device token';
      showToast(message, '', 'warning');
    } finally {
      setRotating(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-slate-900">Device Sync</h2>
          <p className="text-sm text-slate-500">Keep your tickets available offline on this device.</p>
        </div>
        <button
          type="button"
          onClick={() => sync()}
          disabled={loading}
          className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
        >
          {loading ? 'Syncing...' : 'Sync Now'}
        </button>
      </div>

      <div className="bg-white border border-slate-200 rounded-xl shadow-sm p-4 space-y-2 text-sm text-slate-700">
        <div className="flex items-center justify-between">
          <span className="font-semibold">Device token</span>
          <span className="font-mono text-xs text-slate-500 break-all">{deviceToken ?? '—'}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="font-semibold">Last synced</span>
          <span>{lastSyncedAt ? new Date(lastSyncedAt).toLocaleString() : 'Never'}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="font-semibold">Sync version</span>
          <span>{syncVersion}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="font-semibold">Cached tickets</span>
          <span>{tickets.length}</span>
        </div>
      </div>

      <div>
        <button
          type="button"
          onClick={handleRotate}
          disabled={rotating}
          className="px-4 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        >
          {rotating ? 'Rotating...' : 'Rotate Device Token'}
        </button>
        <p className="mt-2 text-xs text-slate-500">Rotation invalidates the current token server-side and generates a new local token.</p>
      </div>

      {error && <div className="p-4 rounded-md bg-red-50 text-red-800 text-sm">{error}</div>}

      <div className="text-xs text-slate-500">
        Tickets are fetched from <code>/api/me/tickets/for-offline-sync</code> and stored in IndexedDB for offline access.
      </div>
    </div>
  );
};

export default DeviceLocalizationSyncPage;
