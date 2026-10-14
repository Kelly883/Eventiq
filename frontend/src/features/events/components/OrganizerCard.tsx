import React from 'react';

export interface OrganizerCardProps {
  organizer: import('../types/shared').Organizer;
  onClick?: () => void;
}

const OrganizerCard = ({ organizer, onClick }: OrganizerCardProps) => {
  const avatarUrl = organizer.avatarUrl;
  const displayName = organizer.displayName || organizer.userId || 'Unknown Organizer';
  const bio = organizer.bio ? organizer.bio.substring(0, 100) + '...' : '';
  const profilePath = organizer.publicUrl || `/o/${organizer.userId}`;

  const firstChar = displayName?.charAt(0) || '';
  const lastChar = displayName?.lastIndexOf(' ') > -1 ? displayName.charAt(displayName.length - 1) : '';
  const initials = `${firstChar}${lastChar}`.toUpperCase().trim() || 'UN';

  return (
    <div className="organizer-card cursor-pointer" onClick={onClick} role="button" tabIndex="0" aria-label={`Organizer profile: ${displayName}`}>
      <div className="organizer-card__avatar">
        {avatarUrl ? (
          <img
            src={avatarUrl}
            alt={displayName}
            className="organizer-card__avatar-image"
          />
        ) : (
          <div className="organizer-card__avatar-placeholder">
            <span className="organizer-card__avatar-initials">{initials}</span>
          </div>
        )}
      </div>
      <div className="organizer-card__info">
        <h4 className="organizer-card__name">{displayName}</h4>
        {bio && <p className="organizer-card__bio">{bio}</p>}
      </div>
      <a
        href={profilePath}
        className="organizer-card__profile-link"
        target="_blank"
        rel="noopener noreferrer"
      >
        View Profile
      </a>
    </div>
  );
};

export default OrganizerCard;