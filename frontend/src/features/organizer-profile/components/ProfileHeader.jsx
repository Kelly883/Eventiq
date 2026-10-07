import React from 'react';
import './OrganizerProfileComponents.css';

const ProfileHeader = ({ organizer, isEditable = false, onEditClick }) => {
  if (!organizer) {
    return (
      <div className="profile-header">
        <div className="profile-header__avatar profile-header__avatar--placeholder">
          ?
        </div>
        <div className="profile-header__info">
          <h2 className="profile-header__name">Unknown Organizer</h2>
        </div>
      </div>
    );
  }

  const displayName = organizer.displayName || organizer.user?.name || 'Unnamed Organizer';
  const bio = organizer.bio || '';
  const avatarUrl = organizer.avatarUrl || organizer.avatar_url;
  const primaryColor = organizer.brandingColors?.primaryColor || '#4ecdc4';
  const accentColor = organizer.brandingColors?.accentColor || '#cc3838';

  return (
    <div
      className="profile-header"
      style={{
        background: `linear-gradient(135deg, ${primaryColor}22, ${accentColor}22)`,
      }}
    >
      <div className="profile-header__avatar-wrapper">
        {avatarUrl ? (
          <img
            src={avatarUrl}
            alt={displayName}
            className="profile-header__avatar"
          />
        ) : (
          <div
            className="profile-header__avatar profile-header__avatar--placeholder"
            style={{ backgroundColor: primaryColor, color: '#ffffff' }}
          >
            {displayName.charAt(0).toUpperCase()}
          </div>
        )}
        {isEditable && (
          <button
            type="button"
            onClick={onEditClick}
            className="profile-header__edit-btn"
            aria-label="Edit profile"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
              <path d="m15 5 4 4" />
            </svg>
          </button>
        )}
      </div>
      <div className="profile-header__info">
        <h2 className="profile-header__name">{displayName}</h2>
        {bio && <p className="profile-header__bio">{bio}</p>}
      </div>
    </div>
  );
};

export default ProfileHeader;
