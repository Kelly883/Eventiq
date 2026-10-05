import React, { useState } from 'react';
import type { PermissionItem } from '../types';
import './RolesComponents.css';

export const PERMISSION_CATEGORIES = {
  'Event Management': ['events:read', 'events:write', 'events:delete'],
  'Ticket Management': ['tickets:read', 'tickets:write', 'tickets:delete'],
  Analytics: ['analytics:read', 'analytics:export'],
  'User Management': ['users:read', 'users:write', 'users:delete'],
} as const;

export interface PermissionCardProps {
  category: string;
  permissions: PermissionItem[];
  isAdmin?: boolean;
  onTogglePermission?: (permName: string, checked: boolean) => void;
  isLoading?: boolean;
  error?: string;
  onRetry?: () => void;
}

const PermissionCard: React.FC<PermissionCardProps> = ({
  category,
  permissions = [],
  isAdmin = false,
  onTogglePermission,
  isLoading = false,
  error,
  onRetry,
}) => {
  const [isOpen, setIsOpen] = useState(true);

  if (isLoading) {
    return (
      <div className="permission-card permission-card--loading" role="status" aria-label="Loading permissions" data-testid={`permission-card-${category.toLowerCase().replace(/\s+/g, '-')}`}>
        <div className="permission-card__skeleton-header" />
        <div className="permission-card__skeleton-body">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="permission-card__skeleton-row" />
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="permission-card permission-card--error" role="alert" data-testid={`permission-card-${category.toLowerCase().replace(/\s+/g, '-')}`}>
        <p className="permission-card__error-message">{error}</p>
        {onRetry && (
          <button type="button" onClick={onRetry} className="permission-card__retry" data-testid={`permission-card-retry-${category.toLowerCase().replace(/\s+/g, '-')}`}>
            Retry
          </button>
        )}
      </div>
    );
  }

  const categorySlug = category.toLowerCase().replace(/\s+/g, '-');

  return (
    <div className="permission-card" data-testid={`permission-card-${categorySlug}`}>
      <button
        type="button"
        className="permission-card__header"
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
        data-testid={`permission-card-header-${categorySlug}`}
      >
        <span className="permission-card__title">{category}</span>
        <span className="permission-card__count">{permissions.length}</span>
        <span className="permission-card__chevron" aria-hidden="true">
          {isOpen ? '▾' : '▸'}
        </span>
      </button>
      {isOpen && (
        <div className="permission-card__body" data-testid={`permission-card-body-${categorySlug}`}>
          {permissions.length === 0 ? (
            <p className="permission-card__empty">No permissions in this category.</p>
          ) : (
            <ul className="permission-card__list">
              {permissions.map((perm) => {
                const permName = typeof perm === 'string' ? perm : perm?.name;
                const permDescription = typeof perm === 'string' ? '' : perm?.description;
                const isEnabled = typeof perm === 'object' ? perm.enabled !== false : true;

                return (
                  <li key={permName} className="permission-card__item" data-testid={`permission-item-${permName}`}>
                    <div className="permission-card__item-info">
                      <span className="permission-card__item-name">{permName}</span>
                      {permDescription && (
                        <span className="permission-card__item-desc">{permDescription}</span>
                      )}
                    </div>
                    {isAdmin && onTogglePermission ? (
                      <label className="permission-card__toggle">
                        <input
                          type="checkbox"
                          checked={isEnabled}
                          onChange={(e) => onTogglePermission(permName, e.target.checked)}
                          aria-label={`${permName}${permDescription ? `: ${permDescription}` : ''}`}
                          data-testid={`permission-toggle-${permName}`}
                        />
                        <span className="permission-card__toggle-slider" aria-hidden="true" />
                      </label>
                    ) : (
                      <span
                        className={`permission-card__badge ${isEnabled ? 'permission-card__badge--enabled' : 'permission-card__badge--disabled'}`}
                        data-testid={`permission-badge-${permName}`}
                      >
                        {isEnabled ? 'Enabled' : 'Disabled'}
                      </span>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      )}
    </div>
  );
};

export default PermissionCard;
