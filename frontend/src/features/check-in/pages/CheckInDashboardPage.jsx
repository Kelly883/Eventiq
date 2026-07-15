import React, { useState, useEffect } from 'react';
import {
  CheckInStatsDisplay,
  CheckInQRScanner,
  CheckInSearchBar,
} from '../components';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';
import Skeleton from '../../../components/Skeleton';
import EmptyState from '../../../components/EmptyState';

const CheckInDashboardPage = () => {
  const [isPageLoading, setIsPageLoading] = useState(true);
  const isOnline = useOfflineSyncStore((state) => state.isOnline);
  const queue = useOfflineSyncStore((state) => state.queue);
  const history = useOfflineSyncStore((state) => state.history);
  const isSyncing = useOfflineSyncStore((state) => state.isSyncing);
  const syncQueue = useOfflineSyncStore((state) => state.syncQueue);
  const clearSyncedHistory = useOfflineSyncStore((state) => state.clearSyncedHistory);

  // Simulate initial config loading to showcase our reusable skeleton states
  useEffect(() => {
    const timer = setTimeout(() => {
      setIsPageLoading(false);
    }, 1200);
    return () => clearTimeout(timer);
  }, []);

  if (isPageLoading) {
    return (
      <div className="space-y-8 max-w-7xl mx-auto p-6 md:p-10">
        <div className="space-y-3">
          <Skeleton variant="text" className="h-8 w-1/3" />
          <Skeleton variant="text" className="h-4 w-1/2" />
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <Skeleton variant="text" className="h-24 rounded-2xl" count={3} />
        </div>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2">
            <Skeleton variant="card" className="h-64" />
          </div>
          <div>
            <Skeleton variant="list" count={4} />
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl space-y-8">
        
        {/* Connection Status Banner */}
        <div className={`p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm transition-all duration-300 ${
          isOnline 
            ? 'bg-emerald-50 border-emerald-100/80 text-emerald-800' 
            : 'bg-amber-50 border-amber-100/80 text-amber-800'
        }`}>
          <div className="flex items-center gap-3">
            <span className={`relative flex h-3 w-3 ${isOnline ? 'text-emerald-500' : 'text-amber-500'}`}>
              <span className={`animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 ${isOnline ? 'bg-emerald-400' : 'bg-amber-400'}`}></span>
              <span className={`relative inline-flex rounded-full h-3 w-3 ${isOnline ? 'bg-emerald-500' : 'bg-amber-500'}`}></span>
            </span>
            <div>
              <p className="text-sm font-extrabold tracking-tight">
                {isOnline ? 'Connection Status: Online Mode' : 'Connection Status: Offline Buffer Mode'}
              </p>
              <p className="text-xs opacity-85 mt-0.5">
                {isOnline 
                  ? 'Real-time validations are synchronized instantly with the cloud backend.' 
                  : 'Scans are saved securely in local storage and will sync automatically upon reconnection.'}
              </p>
            </div>
          </div>
          
          {queue.length > 0 && isOnline && (
            <button
              onClick={syncQueue}
              disabled={isSyncing}
              className="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 text-white font-bold text-xs rounded-lg transition-all shadow-sm shadow-indigo-100 cursor-pointer flex items-center gap-2"
            >
              {isSyncing ? (
                <>
                  <span className="h-3 w-3 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Syncing...
                </>
              ) : (
                <>🔄 Sync Now ({queue.length})</>
              )}
            </button>
          )}
        </div>

        {/* Dashboard Header */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <span className="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100/80 uppercase tracking-widest inline-block mb-2">
              On-Site Venue Logistics
            </span>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Ticket Check-In Desk</h1>
            <p className="mt-1.5 text-sm text-slate-500">
              Process attendee check-ins, view queue logs, and monitor attendance metrics offline or online.
            </p>
          </div>
        </div>

        {/* Metrics display */}
        <CheckInStatsDisplay total={150} checkedIn={35} />

        {/* Core Layout Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main scanner/manual input */}
          <div className="lg:col-span-2 space-y-6">
            <CheckInQRScanner eventId={1} />
            <CheckInSearchBar eventId={1} />
          </div>

          {/* Sync logs and recent checks side panel */}
          <div className="space-y-6">
            
            {/* Sync Buffer Queue card */}
            <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
              <div className="flex items-center justify-between mb-4 border-b border-slate-50 pb-3">
                <h3 className="font-bold text-slate-800 text-sm flex items-center gap-2">
                  <span>📥 Buffer Queue</span>
                  <span className="bg-amber-50 border border-amber-100 text-amber-600 text-[10px] px-2 py-0.5 rounded-full font-bold">
                    {queue.length} pending
                  </span>
                </h3>
              </div>

              {queue.length === 0 ? (
                <div className="text-center py-6 text-slate-400 text-xs italic">
                  No pending offline scans in queue
                </div>
              ) : (
                <div className="space-y-3 max-h-60 overflow-y-auto pr-1">
                  {queue.map((item) => (
                    <div key={item.id} className="p-3 bg-amber-50/40 border border-amber-100/60 rounded-xl flex items-center justify-between text-xs">
                      <div>
                        <span className="font-mono font-bold text-amber-900 block">{item.ticketCode}</span>
                        <span className="text-[10px] text-slate-400">
                          Scanned at {new Date(item.scannedAt).toLocaleTimeString()}
                        </span>
                      </div>
                      <span className="text-[9px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-bold uppercase tracking-wider animate-pulse">
                        {item.status}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Recent History Scans card */}
            <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
              <div className="flex items-center justify-between mb-4 border-b border-slate-50 pb-3">
                <h3 className="font-bold text-slate-800 text-sm">Scan Activity History</h3>
                {history.length > 0 && (
                  <button
                    onClick={clearSyncedHistory}
                    className="text-[10px] text-slate-400 hover:text-rose-600 transition-colors"
                  >
                    Clear Synced
                  </button>
                )}
              </div>

              {history.length === 0 ? (
                <div className="text-center py-12">
                  <span className="text-2xl block mb-2 filter grayscale">📋</span>
                  <p className="text-xs text-slate-400 italic">No tickets processed in this session</p>
                </div>
              ) : (
                <div className="space-y-3 max-h-96 overflow-y-auto pr-1">
                  {history.map((item) => (
                    <div
                      key={item.id}
                      className={`p-3 rounded-xl border text-xs flex flex-col gap-1.5 transition-all ${
                        item.status === 'synced'
                          ? 'bg-slate-50/50 border-slate-100'
                          : 'bg-rose-50/40 border-rose-100 text-rose-900'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <span className="font-mono font-bold text-slate-900">{item.ticketCode}</span>
                        <span className={`text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider ${
                          item.status === 'synced'
                            ? 'bg-emerald-50 text-emerald-600 border border-emerald-100/50'
                            : 'bg-rose-100 text-rose-700 border border-rose-200/50'
                        }`}>
                          {item.status === 'synced' ? 'Synced' : 'Failed'}
                        </span>
                      </div>
                      
                      {item.error && (
                        <p className="text-[10px] text-rose-600 font-medium leading-relaxed bg-rose-50 p-2 rounded-lg border border-rose-100/30">
                          ⚠️ {item.error}
                        </p>
                      )}

                      <div className="flex justify-between items-center text-[9px] text-slate-400">
                        <span>{new Date(item.scannedAt).toLocaleTimeString()}</span>
                        <span>Event ID: {item.eventId}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

          </div>
        </div>

      </div>
    </div>
  );
};

export default CheckInDashboardPage;
