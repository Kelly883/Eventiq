import React from 'react';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';

export const CheckInStatsDisplay = ({ total = 120, checkedIn = 45 }) => {
  const queue = useOfflineSyncStore((state) => state.queue);
  const history = useOfflineSyncStore((state) => state.history);
  
  // Calculate stats
  const pendingSyncCount = queue.length;
  const syncedOfflineCount = history.filter(h => h.status === 'synced').length;
  
  // Adjusted live checked-in count based on scans
  const liveCheckedIn = checkedIn + syncedOfflineCount;
  const percent = Math.min(Math.round((liveCheckedIn / total) * 100), 100);

  return (
    <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
      {/* Percentage Circle Card */}
      <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
        <div className="relative flex items-center justify-center h-16 w-16 flex-shrink-0">
          <svg className="h-full w-full transform -rotate-90">
            <circle
              cx="32"
              cy="32"
              r="28"
              stroke="#f1f5f9"
              strokeWidth="5"
              fill="transparent"
            />
            <circle
              cx="32"
              cy="32"
              r="28"
              stroke="#4f46e5"
              strokeWidth="5"
              fill="transparent"
              strokeDasharray={175.9}
              strokeDashoffset={175.9 - (175.9 * percent) / 100}
              className="transition-all duration-500 ease-out"
            />
          </svg>
          <span className="absolute text-xs font-black text-slate-800">{percent}%</span>
        </div>
        <div>
          <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Attendance Rate</span>
          <span className="text-xl font-extrabold text-slate-800">{liveCheckedIn} / {total}</span>
        </div>
      </div>

      {/* Offline Status Card */}
      <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 font-bold">
          {pendingSyncCount}
        </div>
        <div>
          <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Pending Offline Sync</span>
          <span className="text-lg font-bold text-slate-800">
            {pendingSyncCount > 0 ? `${pendingSyncCount} scan(s) queued` : 'All synced'}
          </span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Stored securely in LocalStorage</span>
        </div>
      </div>

      {/* Speed Metrics Card */}
      <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 text-lg">
          ⏱️
        </div>
        <div>
          <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Average Scan Speed</span>
          <span className="text-lg font-bold text-slate-800">1.4s / ticket</span>
          <span className="text-[10px] text-emerald-600 font-semibold block mt-0.5">Optimized for on-site traffic</span>
        </div>
      </div>
    </div>
  );
};

export default CheckInStatsDisplay;
