import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import Skeleton from '../../../components/Skeleton';
import EmptyState from '../../../components/EmptyState';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';

/**
 * Organizer Event List Page — dashboard for managing events.
 * Displays events grouped by status (Upcoming / Past / Drafts)
 * with a persistent Create New Event CTA.
 */
const OrganizerEventListPage = () => {
  const { organizerId } = useAuthContext();
  const navigate = useNavigate();
  const [events, setEvents] = useState([]);
  const [statusFilter, setStatusFilter] = useState('upcoming');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    async function fetchEvents() {
      setLoading(true);
      try {
        const res = await api.get('/organizer/events', {
          params: { status: statusFilter },
        });
        if (!cancelled) setEvents(res.data.events || res.data.data || []);
      } catch (err) {
        if (!cancelled) console.error(err);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchEvents();
    return () => { cancelled = true; };
  }, [statusFilter]);

  const handleCreate = () => navigate('/organizer/events/create');

  const handleDelete = async (eventId) => {
    if (window.confirm('Delete this event?')) {
      try {
        await api.delete(`/organizer/events/${eventId}`);
        setEvents((prev) => prev.filter((e) => String(e.id) !== String(eventId)));
      } catch (err) {
        console.error(err);
      }
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-7xl">
          <Skeleton variant="table" count={5} />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif', lineHeight: '1.2' }}>
            My Events
          </h1>
          <p className="mt-2 text-sm text-[#999999]">Manage your upcoming, past, and draft events.</p>
        </div>

        {/* Create CTA - always visible */}
        <div className="bg-white rounded-xl p-4 md:p-6 shadow-sm mb-6 border border-[#E3E4E6]">
          <Link
            to="/organizer/events/create"
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] transition-colors"
          >
            + Create New Event
          </Link>
        </div>

        {/* Tabs for status filtering */}
        <div className="flex gap-1 bg-white rounded-xl p-1 mb-6 shadow-sm border border-[#E3E4E6]">
          <button
            type="button"
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${statusFilter === 'upcoming' ? 'bg-[#FF6B6B] text-white' : 'text-[#333333] hover:bg-[#F7F8FA]'}`}
            onClick={() => setStatusFilter('upcoming')}
          >
            Upcoming
          </button>
          <button
            type="button"
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${statusFilter === 'past' ? 'bg-[#FF6B6B] text-white' : 'text-[#333333] hover:bg-[#F7F8FA]'}`}
            onClick={() => setStatusFilter('past')}
          >
            Past
          </button>
          <button
            type="button"
            className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${statusFilter === 'draft' ? 'bg-[#FF6B6B] text-white' : 'text-[#333333] hover:bg-[#F7F8FA]'}`}
            onClick={() => setStatusFilter('draft')}
          >
            Drafts
          </button>
        </div>

        {/* Events list */}
        <div className="bg-white rounded-xl border border-[#E3E4E6] shadow-sm p-6 md:p-8">
          {events.length === 0 ? (
            statusFilter === 'upcoming' ? (
              <EmptyState
                icon="📅"
                title="You haven't created any events yet."
                description="Get started by creating your first event. It's quick and easy."
                actionLabel="Create New Event"
                onAction={handleCreate}
              />
            ) : (
              <EmptyState
                icon="📅"
                title="No events found"
                description="Try adjusting the filters above or create a new event."
              />
            )
          ) : (
            <div className="space-y-4">
              {events.map((event) => (
                <div
                  key={event.id}
                  className="border border-[#E3E4E6] rounded-lg p-4 md:p-6 hover:bg-[#F7F8FA] transition-colors"
                >
                  <div className="flex justify-between items-start">
                    <h3 className="text-lg font-semibold text-[#333333]">
                      {event.title}
                    </h3>
                    <span
                      className={`inline-flex items-center gap-2 px-2 py-1 rounded text-xs font-medium ${
                        event.status === 'past'
                          ? 'bg-[#F7F8FA] text-[#999999] border border-[#E3E4E6]'
                          : 'bg-[#FF9E9E] text-[#CC3838] border border-[#FF6B6B]/20'
                      }`}
                    >
                      {event.status || statusFilter}
                    </span>
                  </div>
                  <p className="mt-1 text-sm text-[#999999]">
                    {event.startDate ? new Date(event.startDate).toLocaleDateString() : '—'}
                  </p>
                  <p className="mt-1 text-sm text-[#B3B3B3]">
                    Tickets sold: {event.ticketsSold ?? 0} / {event.capacity ?? 0}
                  </p>
                  <div className="mt-3 flex flex-wrap gap-2">
                    <Link
                      to={`/organizer/events/${event.id}/edit`}
                      className="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#FF6B6B] border border-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white transition-colors"
                    >
                      Edit
                    </Link>
                    <Link
                      to={`/organizer/events/${event.id}/ticketing`}
                      className="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium bg-[#FF6B6B] text-white hover:bg-[#D94545] transition-colors"
                    >
                      🎟️ Ticket Tiers
                    </Link>
                    <Link
                      to={`/organizer/events/${event.id}/pricing`}
                      className="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
                    >
                      💰 Pricing
                    </Link>
                    <button
                      type="button"
                      onClick={() => handleDelete(event.id)}
                      className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#999999] hover:bg-[#F7F8FA] border border-transparent hover:border-[#E3E4E6] transition-colors"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default OrganizerEventListPage;
