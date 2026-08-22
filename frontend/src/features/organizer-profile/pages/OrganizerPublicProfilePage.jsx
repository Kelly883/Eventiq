import React, { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { organizerService } from '../services';

const OrganizerPublicProfilePage = () => {
  const { organizerId } = useParams();
  const navigate = useNavigate();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const isMe = organizerId === 'me';

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        setLoading(true);
        setError(null);
        const data = isMe
          ? await organizerService.getMyProfile()
          : await organizerService.getPublicProfile(organizerId);
        setProfile(data.data);
      } catch (err) {
        setError(err.response?.data?.message || 'Failed to load profile');
      } finally {
        setLoading(false);
      }
    };

    fetchProfile();
  }, [organizerId, isMe]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-slate-500 font-semibold">Loading profile...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center gap-4">
        <div className="text-rose-600 font-semibold">{error}</div>
        <button
          onClick={() => navigate(-1)}
          className="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300 transition-colors"
        >
          Go Back
        </button>
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="min-h-screen bg-slate-50 flex flex-col items-center justify-center gap-4">
        <div className="text-slate-500 font-semibold">Profile not found</div>
        <button
          onClick={() => navigate(-1)}
          className="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 font-semibold hover:bg-slate-300 transition-colors"
        >
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors"
        >
          ← Back
        </button>

        <div className="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
          <div className="flex items-start gap-6">
            {profile.avatarUrl && (
              <img
                src={profile.avatarUrl}
                alt={profile.displayName}
                className="h-24 w-24 rounded-full object-cover border-2 border-slate-100"
              />
            )}
            <div className="flex-1">
              <h1 className="text-2xl font-extrabold text-slate-900">{profile.displayName}</h1>
              {profile.emailPublic && (
                <p className="mt-1 text-sm text-slate-500">{profile.email}</p>
              )}
              {profile.phonePublic && (
                <p className="mt-1 text-sm text-slate-500">{profile.phone}</p>
              )}
              {profile.website && (
                <a
                  href={profile.website}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-1 text-sm text-indigo-600 hover:text-indigo-700 font-medium"
                >
                  {profile.website}
                </a>
              )}
            </div>
          </div>

          {profile.bio && (
            <div className="mt-6">
              <h2 className="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Bio</h2>
              <p className="text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">{profile.bio}</p>
            </div>
          )}

          {!profile.hideSocialLinks && profile.socialLinks && Object.keys(profile.socialLinks).length > 0 && (
            <div className="mt-6">
              <h2 className="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2">Social</h2>
              <div className="flex flex-wrap gap-3">
                {Object.entries(profile.socialLinks).map(([platform, url]) => (
                  <a
                    key={platform}
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-sm text-indigo-600 hover:text-indigo-700 font-medium capitalize"
                  >
                    {platform}
                  </a>
                ))}
              </div>
            </div>
          )}

          <div className="mt-8 pt-6 border-t border-slate-100">
            <h2 className="text-sm font-bold text-slate-400 uppercase tracking-wider mb-3">Events</h2>
            <Link
              to={`/organizers/${profile.id}/events`}
              className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors"
            >
              View Events
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrganizerPublicProfilePage;
