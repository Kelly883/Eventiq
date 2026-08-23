import React, { useEffect, useState } from 'react';
import { api } from '../../../lib/api';
import { LoadingSpinner } from '../../common';

/**
 * Shared organizer profile renderer.
 *
 * Handles the full state map: loading, not-found/deleted, "created but never
 * set up" (no display name, bio, or avatar), and the populated view. An
 * optional `actions` node is rendered above the profile for owners.
 */
const hasAnyProfileContent = (organizer) =>
  Boolean(organizer?.displayName || organizer?.bio || organizer?.avatarUrl);

const OrganizerProfileView = ({ organizerId, actions = null }) => {
  const [organizer, setOrganizer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!organizerId) {
      setLoading(false);
      return;
    }

    api.get(`/organizers/${organizerId}`)
      .then((res) => setOrganizer(res.data.data))
      .catch((err) => {
        console.error('Failed to fetch organizer profile:', err);
        setFailed(true);
      })
      .finally(() => setLoading(false));
  }, [organizerId]);

  if (loading) {
    return <LoadingSpinner message="Loading profile..." />;
  }

  if (failed || !organizer) {
    return (
      <div className="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
        <div className="text-4xl mb-4">🔍</div>
        <h1 className="text-xl font-bold text-slate-900 mb-2">Organizer Not Found</h1>
        <p className="text-sm text-slate-500">
          This organizer profile doesn&apos;t exist or is no longer available.
        </p>
      </div>
    );
  }

  if (!hasAnyProfileContent(organizer)) {
    return (
      <div className="bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
        <div className="text-4xl mb-4">🌱</div>
        <h1 className="text-xl font-bold text-slate-900 mb-2">Profile Not Set Up Yet</h1>
        <p className="text-sm text-slate-500 max-w-md mx-auto">
          {actions
            ? 'Your public profile is still empty. Add a display name and bio so attendees know who you are.'
            : 'This organizer hasn\u2019t set up their public profile yet. Check back soon.'}
        </p>
        {actions}
      </div>
    );
  }

  return (
    <div>
      {actions && <div className="mb-6">{actions}</div>}
      <h1>Organizer Public Profile</h1>
      <div>
        {organizer.displayName && <h2>{organizer.displayName}</h2>}
        {organizer.bio && <p>{organizer.bio}</p>}
        {organizer.avatarUrl && (
          <img
            src={organizer.avatarUrl}
            alt={`${organizer.displayName || 'Organizer'} avatar`}
            className="w-20 h-20 rounded-full object-cover"
          />
        )}
        <p>Organizer ID: {organizer.organizerId}</p>
        <hr />
        <p>Total Events Created: {organizer.totalEventsCreated}</p>
        <p>Total Tickets Sold: {organizer.totalTicketsSold}</p>
      </div>
    </div>
  );
};

export default OrganizerProfileView;
