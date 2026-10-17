import React from 'react';
import type { SharedOrganizer } from '../types/shared';

export interface OrganizerCardProps {
  organizer: SharedOrganizer;
  onClick?: () => void;
}

const OrganizerCard = ({ organizer, onClick }: OrganizerCardProps) => {
  const avatarUrl = organizer.avatarUrl || organizer.avatar;
  const displayName = organizer.displayName || organizer.name || 'Unknown Organizer';
  const bio = organizer.bio ? organizer.bio.substring(0, 100) + '...' : '';
  const profilePath: string = organizer.publicUrl || `/o/${String(organizer.id ?? '')}`;

  const firstChar = displayName?.charAt(0) || '';
  const initials = firstChar.toUpperCase() || 'UN';

  return (
    <div
      className="organizer-card"
      onClick={onClick}
      role="button"
      tabIndex={0}
      aria-label={`Organizer profile: ${displayName}`}
    >
      <div className="organizer-card__avatar">
        {avatarUrl ? (
          <img
            src={avatarUrl}
            alt={displayName}
            className="organizer-card__avatar-image"
            loading="lazy"
            onError={() => {
              // fallback handled by CSS placeholder
            }}
          />
        ) : (
          <div
            className="organizer-card__avatar-placeholder"
            role="img"
            aria-label={displayName}
            title={displayName}
          >
            <span className="organizer-card__avatar-initials">{initials}</span>
          </div>
        )}
      </div>
      <div className="organizer-card__info">
        <h4 className="organizer-card__name" title={displayName}>
          {displayName}
        </h4>
        {bio && (
          <p
            className="organizer-card__bio"
            title={organizer.bio || undefined}
          >
            {bio}
          </p>
        )}
      </div>
      <a
        href={profilePath}
        className="organizer-card__profile-link"
        target="_blank"
        rel="noopener noreferrer"
        aria-label={`View ${displayName}'s profile`}
      >
        View Profile
      </a>
    </div>
  );
};

export default React.memo(OrganizerCard);
