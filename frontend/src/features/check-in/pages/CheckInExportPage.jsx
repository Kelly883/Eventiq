import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const CheckInExportPage = () => {
  const { user } = useAuthContext();
  const [searchParams] = useSearchParams();
  const eventId = searchParams.get('eventId');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleExport = async (format) => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get(`/check-in/export`, {
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
        <div className="flex items-center gap-4">
          <Link
            to={eventId ? `/check-in?eventId=${eventId}` : '/check-in'}
            className="text-slate-500 hover:text-slate-700 text-sm font-medium"
          >
            ← Back to Check-In
          </Link>
        </div>

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
          </button>
        </div>
      </div>
    </div>
  );
};

export default CheckInExportPage;
