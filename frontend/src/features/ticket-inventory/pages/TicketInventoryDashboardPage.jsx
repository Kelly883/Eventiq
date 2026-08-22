import React, { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { inventoryService } from '../services';

const TicketInventoryDashboardPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const [summary, setSummary] = useState(null);
  const [inventory, setInventory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [accessDenied, setAccessDenied] = useState(false);

  useEffect(() => {
    if (!eventId) return;

    const fetchData = async () => {
      try {
        const [summaryData, inventoryData] = await Promise.all([
          inventoryService.getSummary(eventId),
          inventoryService.getInventory(eventId),
        ]);
        setSummary(summaryData);
        setInventory(inventoryData);
      } catch (err) {
        const status = err.response?.status;
        if (status === 401 || status === 403) {
          setAccessDenied(true);
        }
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [eventId]);

  if (loading) return <div className="p-6">Loading inventory...</div>;

  if (accessDenied) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-7xl">
          <div className="bg-white p-8 rounded-xl border border-red-100 shadow-sm text-center">
            <h1 className="text-2xl font-bold text-red-600 mb-4">Access Denied</h1>
            <p className="text-slate-600 mb-6">
              You do not have permission to view the inventory for this event.
            </p>
            <button
              onClick={() => navigate(-1)}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
            >
              ← Go Back
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (error) return <div className="p-6 text-red-600">Error: {error}</div>;

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Ticket Inventory</h1>
            <p className="mt-2 text-sm text-slate-500">Event ID: {eventId}</p>
          </div>
          <Link
            to={`/organizer/events/${eventId}/inventory/adjust`}
            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors"
          >
            ⚙️ Adjust Inventory
          </Link>
        </div>

        {summary && (
          <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <h2 className="text-lg font-bold text-slate-800 mb-4">Summary</h2>
            <pre className="text-xs bg-slate-50 p-4 rounded-lg overflow-auto">
              {JSON.stringify(summary, null, 2)}
            </pre>
          </div>
        )}

        {inventory && (
          <div className="mt-6 bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <h2 className="text-lg font-bold text-slate-800 mb-4">Detailed Inventory</h2>
            <pre className="text-xs bg-slate-50 p-4 rounded-lg overflow-auto">
              {JSON.stringify(inventory, null, 2)}
            </pre>
          </div>
        )}
      </div>
    </div>
  );
};

export default TicketInventoryDashboardPage;
