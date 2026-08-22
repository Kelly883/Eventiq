import React, { useEffect, useState } from 'react';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { useParams } from 'react-router-dom';
import { api } from '../../../lib/api';

const MyOrganizerProfilePage = () => {
  const { organizerId, user } = useAuthContext();
  const { organizerId: paramOrganizerId } = useParams();

  // Use organizerId from auth context (the logged-in organizer's ID)
  // Fall back to paramOrganizerId if available (for viewing other organizers)
  const targetOrganizerId = organizerId || paramOrganizerId;

  const [organizer, setOrganizer] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (targetOrganizerId) {
      api.get(`/organizers/${targetOrganizerId}`)
        .then((res) => {
          setOrganizer(res.data.data);
        })
        .catch((err) => {
          console.error('Failed to fetch organizer profile:', err);
        })
        .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, [targetOrganizerId]);

  if (loading) {
    return <div>Loading organizer profile...</div>;
  }

  return (
    <div>
      <h1>{organizer ? organizer.displayName : 'My Organizer Profile'}</h1>
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

export default MyOrganizerProfilePage;