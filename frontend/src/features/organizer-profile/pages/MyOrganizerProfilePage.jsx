import React from 'react';
import { Link } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { OrganizerProfileView } from '../components';

/**
 * The organizer's profile home: shows their public profile as attendees see
 * it, with owner actions to edit details or settings. Single destination for
 * everything profile-related.
 */
const MyOrganizerProfilePage = () => {
  const { organizerId } = useAuthContext();

  const actions = (
    <div className="flex flex-wrap items-center justify-center gap-3">
      <Link
        to="/organizer/profile/edit"
        className="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
      >
        ✏️ Edit Profile
      </Link>
      <Link
        to="/organizer/profile/settings"
        className="inline-flex px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors"
      >
        ⚙️ Settings
      </Link>
      {organizerId && (
        <Link
          to={`/organizer/${organizerId}`}
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
        >
          View public profile →
        </Link>
      )}
    </div>
  );

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <OrganizerProfileView organizerId={organizerId} actions={actions} />
      </div>
    </div>
  );
};

export default MyOrganizerProfilePage;
