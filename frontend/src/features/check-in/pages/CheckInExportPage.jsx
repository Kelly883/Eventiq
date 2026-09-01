import React, { useState, useEffect } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import EventSelector from '../../analytics/components/EventSelector';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const CheckInExportPage = () => {
  const { user } = useAuthContext();
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const eventId = searchParams.get('eventId');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleExport = async (format) => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get(`/venue/check-ins/export`, {
        params: { event_id: eventId, format },
        responseType: 'blob',
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `check-in-export-${eventId || 'all'}.${format}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      setError(err?.response?.data?.message || 'Export failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-4xl space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
          <Link
            to={eventId ? `/check-in?eventId=${eventId}` : '/check-in'}
            className="text-slate-500 hover:text-slate-700 text-sm font-medium shrink-0"
          >
            ← Back to Check-In
          </Link>
          <div className="flex-1 max-w-xs">
            <EventSelector
              compact
              selectedEventId={eventId}
              onSelect={(id) => {
                if (id) setSearchParams({ eventId: id });
                else setSearchParams({});
              }}
            />
          </div>
        </div>
        {eventId && (
          <div className="flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl text-sm text-indigo-800 mb-4">
            <span className="h-2 w-2 bg-emerald-500 rounded-full animate-pulse" />
            Exporting data for: <span className="font-bold">Event #{eventId}</span>
          </div>
        )}
        {!eventId && (
          <div className="px-4 py-3 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800 mb-4">
            ⚠️ No event selected — exporting all your check-in records across events.
          </div>
        )}

        <div>
          <h1 className="text-2xl font-extrabold text-slate-900">Export Check-In Data</h1>
          <p className="text-sm text-slate-500 mt-1">
            Download check-in records for compliance and reporting.
          </p>
        </div>

        {error && (
          <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <button
            onClick={() => handleExport('csv')}
            disabled={loading}
            className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all text-left disabled:opacity-50"
          >
            <span className="text-3xl block mb-3">📄</span>
            <h3 className="font-bold text-slate-900">Export as CSV</h3>
            <p className="text-sm text-slate-500 mt-1">
              Spreadsheet format compatible with Excel and Google Sheets.
            </p>
            <p className="text-xs text-indigo-600 mt-1">Best for: Excel, Google Sheets, data analysis</p>
          </button>
          <button
            onClick={() => handleExport('json')}
            disabled={loading}
            className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all text-left disabled:opacity-50"
          >
            <span className="text-3xl block mb-3">📋</span>
            <h3 className="font-bold text-slate-900">Export as JSON</h3>
            <p className="text-sm text-slate-500 mt-1">
              Machine-readable format for integrations and analysis.
            </p>
            <p className="text-xs text-indigo-600 mt-1">Best for: APIs, databases, automated processing</p>
          </button>
        </div>
      </div>
    </div>
  );
};

export default CheckInExportPage;
