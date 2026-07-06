import React from 'react';
import { useEventModeration } from '../hooks/useEventModeration';
import {
  EventTable,
  EventDetailsPanel,
  EventFilters,
  ModerationNotes,
} from '../components/events';

const AdminEventModerationPage = () => {
  const {
    loading,
    error,
    events,
    selectedEvent,
    filters,
    setFilters,
    moderationNotes,
    setSelectedEvent,
    updateModeration,
  } = useEventModeration();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Event Moderation</h1>
      </div>

      <EventFilters filters={filters} onFiltersChange={setFilters} />

      {error && <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>}

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div className="lg:col-span-2">
            <EventTable loading={loading} events={events} onRowClick={(e) => setSelectedEvent(e)} />
          </div>
          <div>
            <EventDetailsPanel loading={loading} event={selectedEvent} />
            <ModerationNotes
              loading={loading}
              notes={moderationNotes}
              onChange={(notes) => updateModeration({ notes })}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminEventModerationPage;

