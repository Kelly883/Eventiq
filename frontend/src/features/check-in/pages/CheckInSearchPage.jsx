import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const CheckInSearchPage = () => {
  const { user } = useAuthContext();
  const [searchParams] = useSearchParams();
  const eventId = searchParams.get('eventId');
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!user) return;
  }, [user]);

  const handleSearch = async (e) => {
    e.preventDefault();
    if (!query.trim()) return;

    setLoading(true);
    setError('');
    try {
      const response = await api.get(`/check-in/search`, {
        params: { query, event_id: eventId },
      });
      setResults(response.data?.data || []);
    } catch (err) {
      setError(err?.response?.data?.message || 'Search failed. Please try again.');
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
          <h1 className="text-2xl font-extrabold text-slate-900">Search Attendees</h1>
          <p className="text-sm text-slate-500 mt-1">
            Find attendees by ticket code, email, or name.
          </p>
        </div>

        <form onSubmit={handleSearch} className="flex gap-3">
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Enter ticket code, email, or name..."
            className="flex-1 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {loading ? 'Searching...' : 'Search'}
          </button>
        </form>

        {error && (
          <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {error}
          </div>
        )}

        {loading && (
          <div className="space-y-3">
            <Skeleton variant="card" className="h-16" />
            <Skeleton variant="card" className="h-16" />
          </div>
        )}

        {results.length > 0 && (
          <div className="space-y-3">
            <h2 className="text-sm font-semibold text-slate-700">
              {results.length} result{results.length !== 1 ? 's' : ''} found
            </h2>
            {results.map((item) => (
              <div key={item.id} className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-semibold text-slate-900">{item.name || item.email}</p>
                    <p className="text-sm text-slate-500">Ticket: {item.ticket_code}</p>
                  </div>
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                    item.checked_in ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'
                  }`}>
                    {item.checked_in ? 'Checked In' : 'Not Checked In'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}

        {!loading && query && results.length === 0 && (
          <div className="text-center py-12 text-slate-400">
            No attendees found matching "{query}"
          </div>
        )}
      </div>
    </div>
  );
};

export default CheckInSearchPage;
