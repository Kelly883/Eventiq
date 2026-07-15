import React, { useState } from 'react';
import EmptyState from '../../../components/EmptyState';
import { useOfflineSyncStore } from '../../offline/services/offlineSyncStore';

export const CheckInSearchBar = ({ eventId = 1 }) => {
  const [query, setQuery] = useState('');
  const enqueueScan = useOfflineSyncStore((state) => state.enqueueScan);

  const mockAttendees = [
    { name: 'Diana Prince', ticketCode: 'TCK-SUM-2291', status: 'Purchased' },
    { name: 'Arthur Curry', ticketCode: 'TCK-SUM-8841', status: 'Purchased' },
    { name: 'Victor Stone', ticketCode: 'TCK-SUM-1192', status: 'Checked In' },
    { name: 'Barry Allen', ticketCode: 'TCK-SUM-7761', status: 'Purchased' },
  ];

  const filteredAttendees = query
    ? mockAttendees.filter(
        (a) =>
          a.name.toLowerCase().includes(query.toLowerCase()) ||
          a.ticketCode.toLowerCase().includes(query.toLowerCase())
      )
    : [];

  const handleManualCheckIn = (code) => {
    enqueueScan(code, eventId);
    setQuery('');
  };

  return (
    <div className="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
      <h3 className="text-base font-bold text-slate-800 mb-2">Attendee Lookup List</h3>
      <p className="text-xs text-slate-500 mb-4 leading-relaxed">
        Search for ticket holders by name or ticket code to manually complete check-ins.
      </p>

      <div className="relative mb-5">
        <input
          type="text"
          placeholder="Search attendee name or code..."
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold p-3 pl-10 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-slate-400 transition-all"
        />
        <span className="absolute left-3.5 top-3.5 text-slate-400 text-sm">🔍</span>
      </div>

      {query && filteredAttendees.length > 0 && (
        <div className="border border-slate-100 rounded-xl overflow-hidden divide-y divide-slate-100 animate-fadeIn">
          {filteredAttendees.map((a) => (
            <div key={a.ticketCode} className="p-4 flex items-center justify-between hover:bg-slate-50 transition-all">
              <div>
                <span className="font-bold text-slate-800 text-sm block">{a.name}</span>
                <span className="text-xs font-mono text-slate-400">{a.ticketCode}</span>
              </div>
              <div className="flex items-center gap-3">
                <span className={`text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider ${
                  a.status === 'Checked In' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-500'
                }`}>
                  {a.status}
                </span>
                {a.status !== 'Checked In' && (
                  <button
                    onClick={() => handleManualCheckIn(a.ticketCode)}
                    className="px-3 py-1 bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white font-bold text-xs rounded border border-indigo-100 transition-all cursor-pointer"
                  >
                    Check In
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {query && filteredAttendees.length === 0 && (
        <EmptyState
          icon="🎫"
          title="No attendees found"
          description={`We couldn't find any registrations matching "${query}". Verify spelling or enter a fresh ticket code.`}
          actionLabel="Clear Search"
          onAction={() => setQuery('')}
        />
      )}

      {!query && (
        <div className="text-center py-6 text-xs text-slate-400 italic">
          Start typing above to search attendee records
        </div>
      )}
    </div>
  );
};

export default CheckInSearchBar;
