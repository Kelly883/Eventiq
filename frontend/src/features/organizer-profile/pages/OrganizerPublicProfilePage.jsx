import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { OrganizerProfileView } from '../components';

const OrganizerPublicProfilePage = () => {
  const { organizerId } = useParams();

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <OrganizerProfileView
          organizerId={organizerId}
          actions={
            <Link
              to="/events"
              className="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
            >
              Browse Events
            </Link>
          }
        />
      </div>
    </div>
  );
};

export default OrganizerPublicProfilePage;
