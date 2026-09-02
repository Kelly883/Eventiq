import React, { useCallback, useRef, useState } from 'react';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';
import CameraScanner from './CameraScanner';

/*
 * Payload shape check ported from the former VenueCheckInPage: accept the
 * two QR payload families the backend issues (base64/encrypted blobs and
 * dashed ticket codes) and reject everything else before it hits the queue.
 */
const isValidPayload = (payloadString) =>
  payloadString.startsWith('ey') ||
  payloadString.length > 50 ||
  (payloadString.includes('-') && payloadString.length > 8);

export const CheckInQRScanner = ({ eventId = null }) => {
  const [inputCode, setInputCode] = useState('');
  const [scanMessage, setScanMessage] = useState(null);
  const messageTimerRef = useRef(null);
  const enqueueScan = useOfflineSyncStore((state) => state.enqueueScan);
  const isOnline = useOfflineSyncStore((state) => state.isOnline);

  const sampleTickets = [
    { code: 'TCK-SUM-9281', name: 'Alice Smith (VIP)' },
    { code: 'TCK-SUM-3810', name: 'Bob Johnson (General)' },
    { code: 'TCK-SUM-5749', name: 'Charlie Davis (General)' },
    { code: 'TCK-ERR-EXPIRED', name: 'Expired Ticket (Test Error)' },
  ];

  const flashMessage = (type, text) => {
    if (messageTimerRef.current) clearTimeout(messageTimerRef.current);
    setScanMessage({ type, text });
    messageTimerRef.current = setTimeout(() => setScanMessage(null), 4000);
  };

  // Single intake path for BOTH camera scans and manual codes.
  const handleScan = useCallback((rawCode) => {
    const code = String(rawCode || '').trim();
    if (!code) return;

    if (!isValidPayload(code)) {
      flashMessage('error', `Rejected: “${code}” is not a valid ticket QR payload.`);
      return;
    }

    enqueueScan(code, eventId);
    flashMessage(
      'success',
      `Scanned: ${code} — buffered ${isOnline ? 'online' : 'offline (will sync)'}`
    );
  }, [enqueueScan, eventId, isOnline]);

  const onSubmitForm = (e) => {
    e.preventDefault();
    handleScan(inputCode);
    setInputCode('');
  };

  return (
    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-6">
      {/* Live camera viewfinder (real jsQR scanning) */}
      <div className="flex-1 max-w-sm mx-auto">
        <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">
          Live Camera Scanner
        </label>
        <CameraScanner onScan={handleScan} />
      </div>

      {/* Manual entry — same intake path as the camera */}
      <div className="flex-1 flex flex-col justify-between">
        <div>
          <h3 className="text-base font-bold text-slate-800 mb-1">Manual ticket entry</h3>
          <p className="text-xs text-slate-500 leading-relaxed mb-4">
            Type a ticket code when a QR code won&rsquo;t scan — it follows the exact same validation and offline queue as the camera.
          </p>

          <form onSubmit={onSubmitForm} className="flex gap-2 mb-5">
            <input
              type="text"
              aria-label="Ticket code"
              placeholder="e.g. TCK-SUM-9281"
              value={inputCode}
              onChange={(e) => setInputCode(e.target.value)}
              className="flex-1 bg-slate-50 border border-slate-200 text-slate-700 text-xs font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-slate-400"
            />
            <button
              type="submit"
              className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 rounded-lg shadow-sm transition-all flex items-center"
            >
              Scan
            </button>
          </form>

          {scanMessage && (
            <div
              role="status"
              className={`mb-4 p-3 text-xs font-bold rounded-lg animate-fadeIn flex items-center gap-2 border ${
                scanMessage.type === 'error'
                  ? 'bg-rose-50 border-rose-100 text-rose-700'
                  : 'bg-indigo-50 border-indigo-100 text-indigo-700'
              }`}
            >
              <span className="animate-ping h-1.5 w-1.5 bg-current rounded-full" />
              {scanMessage.text}
            </div>
          )}

          <div>
            <span className="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
              Test Presets (Click to Scan)
            </span>
            <div className="space-y-1.5">
              {sampleTickets.map((t) => (
                <button
                  key={t.code}
                  type="button"
                  onClick={() => handleScan(t.code)}
                  className="w-full text-left px-3 py-2 bg-slate-50 hover:bg-indigo-50/50 border border-slate-100 hover:border-indigo-100 rounded-lg text-xs font-medium text-slate-700 transition-all flex items-center justify-between"
                >
                  <span className="font-mono text-slate-900">{t.code}</span>
                  <span className="text-[10px] text-slate-400">{t.name}</span>
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="mt-5 border-t border-slate-100 pt-4 text-[10px] text-slate-400 flex items-center justify-between">
          <span>Idempotency Protected</span>
          <span className="font-mono">v1.3.0-offline</span>
        </div>
      </div>
    </div>
  );
};

export default CheckInQRScanner;
