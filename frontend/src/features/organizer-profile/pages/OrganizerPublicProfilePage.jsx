import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api } from '../../../lib/api';

const OrganizerPublicProfilePage = () => {
  const { organizerId } = useParams();
  const [organizer, setOrganizer] = useState(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (organizerId) {
      api.get(`/organizers/${organizerId}`)
        .then((res) => {
          setOrganizer(res.data.data);
        })
        .catch((err) => {
          console.error('Failed to fetch organizer profile:', err);
          setNotFound(true);
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [organizerId]);

  if (loading) {
    return <div>Loading organizer profile...</div>;
  }

  if (notFound || !organizer) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-md bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
          <div className="text-4xl mb-4">🔍</div>
          <h1 className="text-xl font-bold text-slate-900 mb-2">Organizer Not Found</h1>
          <p className="text-sm text-slate-500 mb-6">
            This organizer profile doesn&apos;t exist or is no longer available.
            Check the link and try again.
          </p>
          <Link
            to="/events"
            className="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
          >
            Browse Events
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div>
      <h1>Organizer Public Profile</h1>
      {organizer && (
        <div>
          {organizer.displayName && <h2>{organizer.displayName}</h2>}
          {organizer.bio && <p>{organizer.bio}</p>}
          {organizer.avatarUrl && <img src={organizer.avatarUrl} alt="Organizer avatar" className="w-20 h-20 rounded-full object-cover" />}
          <p>Organizer ID: {organizer.organizerId}</p>
          <hr />
          <p>Total Events Created: {organizer.totalEventsCreated}</p>
          <p>Total Tickets Sold: {organizer.totalTicketsSold}</p>
        </div>
      )}
    </div>
  );
};

export default OrganizerPublicProfilePage;
