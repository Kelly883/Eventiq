import React from 'react';

export interface ViewToggleProps {
  view: 'grid' | 'map';
  onChange: (view: 'grid' | 'map') => void;
  hasMapData?: boolean;
  className?: string;
}

export const ViewToggle = ({
  view,
  onChange,
  hasMapData = true,
  className = '',
}: ViewToggleProps) => (
  <div className={`view-toggle ${className}`} role="group" aria-label="View options">
    <button
      type="button"
      className={`view-toggle__option ${view === 'grid' ? 'view-toggle__option--active' : ''}`}
      onClick={() => onChange('grid')}
      aria-pressed={view === 'grid'}
      aria-label="Grid view"
      title="Grid view"
    >
      <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
      >
        <rect x="3" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
        <rect x="14" y="14" width="7" height="7" rx="1" />
      </svg>
      <span className="view-toggle__label">Grid</span>
    </button>
    {hasMapData && (
      <button
        type="button"
        className={`view-toggle__option ${view === 'map' ? 'view-toggle__option--active' : ''}`}
        onClick={() => onChange('map')}
        aria-pressed={view === 'map'}
        aria-label="Map view"
        title="Map view"
      >
        <svg
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
          aria-hidden="true"
        >
          <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" />
          <line x1="9" y1="3" x2="9" y2="18" />
          <line x1="15" y1="6" x2="15" y2="21" />
          <circle cx="12" cy="12" r="2" />
        </svg>
        <span className="view-toggle__label">Map</span>
      </button>
    )}
  </div>
);

export default ViewToggle;