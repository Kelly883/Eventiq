import React, { useState } from 'react';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';

export const CheckInQRScanner = ({ eventId = 1 }) => {
  const [inputCode, setInputCode] = useState('');
  const [scanMessage, setScanMessage] = useState(null);
  const enqueueScan = useOfflineSyncStore((state) => state.enqueueScan);
  const isOnline = useOfflineSyncStore((state) => state.isOnline);

  const sampleTickets = [
    { code: 'TCK-SUM-9281', name: 'Alice Smith (VIP)' },
    { code: 'TCK-SUM-3810', name: 'Bob Johnson (General)' },
    { code: 'TCK-SUM-5749', name: 'Charlie Davis (General)' },
    { code: 'TCK-ERR-EXPIRED', name: 'Expired Ticket (Test Error)' },
  ];

  const handleScan = (code) => {
    if (!code.trim()) return;

    enqueueScan(code.trim(), eventId);
    
    setInputCode('');
    setScanMessage({
      type: 'success',
      text: `Scanned: ${code} - Buffered ${isOnline ? 'Online' : 'Offline'}`,
    });

    setTimeout(() => {
      setScanMessage(null);
    }, 4000);
  };

  const onSubmitForm = (e) => {
    e.preventDefault();
    handleScan(inputCode);
  };

  return (
    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col md:flex-row gap-6">
      {/* Viewfinder simulation */}
      <div className="flex-1 max-w-sm mx-auto">
        <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">
          QR Viewfinder (Simulated)
        </label>
        <div className="relative aspect-square w-full rounded-2xl bg-slate-900 overflow-hidden border border-slate-800 flex flex-col items-center justify-center p-6 shadow-inner">
          {/* Decorative scanner grid lines */}
          <div className="absolute inset-4 border-2 border-dashed border-slate-700/50 rounded-xl" />
          
          {/* Laser beam line */}
          <div className="absolute left-0 right-0 h-1 bg-rose-500 opacity-80 shadow-md shadow-rose-500/50 animate-bounce top-1/2" />

          {/* QR Code Graphic or status */}
          <div className="z-10 flex flex-col items-center text-center">
            <span className="text-4xl filter grayscale opacity-45 select-none mb-3">📱</span>
            <p className="text-[10px] font-mono text-slate-500 uppercase tracking-widest">
              Camera is Active
            </p>
          </div>

          <div className="absolute bottom-4 left-4 right-4 bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-lg border border-slate-800 text-center">
            <span className="text-[10px] text-slate-400 font-medium">
              Ready to Scan Ticket Codes
            </span>
          </div>
        </div>
      </div>

      {/* Inputs and helper buttons */}
      <div className="flex-1 flex flex-col justify-between">
        <div>
          <h3 className="text-base font-bold text-slate-800 mb-1">Manual Scan / Test Sandbox</h3>
          <p className="text-xs text-slate-500 leading-relaxed mb-4">
            Type a ticket code manually or use the buttons below to simulate on-site ticket scanning.
          </p>

          <form onSubmit={onSubmitForm} className="flex gap-2 mb-5">
            <input
              type="text"
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
            <div className="mb-4 p-3 bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold rounded-lg animate-fadeIn flex items-center gap-2">
              <span className="animate-ping h-1.5 w-1.5 bg-indigo-500 rounded-full" />
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
          <span className="font-mono">v1.2.0-offline</span>
        </div>
      </div>
    </div>
  );
};

export default CheckInQRScanner;
