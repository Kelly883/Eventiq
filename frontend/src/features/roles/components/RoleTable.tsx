import React, { useState, useMemo, useRef, useEffect, useCallback } from 'react';
import RoleBadge from './RoleBadge.tsx';
import type { User, SortDirection, SortState, OptimisticUpdate } from '../types';
import './RolesComponents.css';

interface SortIconProps {
  active: boolean;
  direction: SortDirection;
}

const SortIcon: React.FC<SortIconProps> = ({ active, direction }) => (
  <span className="role-table__sort" aria-hidden="true">
    {active ? (direction === 'asc' ? '↑' : '↓') : '↕'}
  </span>
);

interface SortButtonProps {
  active: boolean;
  direction: SortDirection;
  onClick: () => void;
  children: React.ReactNode;
}

const SortButton: React.FC<SortButtonProps> = ({ active, direction, onClick, children }) => {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onClick();
    }
  };

  return (
    <button
      type="button"
      onClick={onClick}
      onKeyDown={handleKeyDown}
      aria-label={`Sort by ${children}`}
      aria-sort={active ? (direction === 'asc' ? 'ascending' : 'descending') : 'none'}
      className="role-table__sort-btn"
    >
      {children}
      <SortIcon active={active} direction={direction} />
    </button>
  );
};

interface RoleTableProps {
  users: User[];
  isLoading?: boolean;
  onSelectUser?: (userId: string, checked: boolean) => void;
  onSelectAll?: (checked: boolean) => void;
  selectedUserIds?: string[];
  onSort?: (key: string, direction: SortDirection) => void;
  sortKey?: string;
  sortDirection?: SortDirection;
  error?: string;
  onRetry?: () => void;
}

const RoleTable: React.FC<RoleTableProps> = ({
  users = [],
  isLoading = false,
  onSelectUser,
  onSelectAll,
  selectedUserIds = [],
  onSort,
  sortKey,
  sortDirection,
  error,
  onRetry,
}) => {
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const expandableRefs = useRef<Record<string, HTMLDivElement>>({});

  const allSelected = users.length > 0 && selectedUserIds.length === users.length;
  const someSelected = selectedUserIds.length > 0 && !allSelected;

  const handleSort = useCallback(
    (key: string) => {
      if (!onSort) return;
      const direction = sortKey === key && sortDirection === 'asc' ? 'desc' : 'asc';
      onSort(key, direction);
    },
    [onSort, sortKey, sortDirection]
  );

  const sortedUsers = useMemo(() => {
    if (!onSort || !sortKey) return users;
    return [...users].sort((a, b) => {
      const aVal = a?.[sortKey as keyof User];
      const bVal = b?.[sortKey as keyof User];

      if (sortKey === 'permission_count') {
        const aNum = typeof aVal === 'number' ? aVal : Number(aVal) || 0;
        const bNum = typeof bVal === 'number' ? bVal : Number(bVal) || 0;
        return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
      }

      if (sortKey === 'created_at') {
        const aDate = aVal ? new Date(aVal as string).getTime() : 0;
        const bDate = bVal ? new Date(bVal as string).getTime() : 0;
        return sortDirection === 'asc' ? aDate - bDate : bDate - aDate;
      }

      const aStr = String(aVal ?? '').toLowerCase();
      const bStr = String(bVal ?? '').toLowerCase();
      if (aStr < bStr) return sortDirection === 'asc' ? -1 : 1;
      if (aStr > bStr) return sortDirection === 'asc' ? 1 : -1;
      return 0;
    });
  }, [users, sortKey, sortDirection, onSort]);

  const handleToggleExpand = useCallback((userId: string) => {
    setExpandedId((prev) => (prev === userId ? null : userId));
  }, []);

  const handleExpandKeyDown = useCallback(
    (e: React.KeyboardEvent, userId: string) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        handleToggleExpand(userId);
      }
    },
    [handleToggleExpand]
  );

  useEffect(() => {
    if (expandedId && expandableRefs.current[expandedId]) {
      expandableRefs.current[expandedId].focus();
    }
  }, [expandedId]);

  if (isLoading) {
    return (
      <div className="role-table__skeleton" role="status" aria-label="Loading users">
        {[...Array(5)].map((_, i) => (
          <div key={i} className="role-table__skeleton-row" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="role-table__error" role="alert">
        <p className="role-table__error-message">{error}</p>
        {onRetry && (
          <button type="button" onClick={onRetry} className="role-table__retry">
            Retry
          </button>
        )}
      </div>
    );
  }

  if (!users.length) {
    return (
      <div className="role-table__empty" role="status">
        <p>No users found. Try adjusting your search or filters.</p>
      </div>
    );
  }

  return (
    <div className="role-table-wrapper">
      <table className="role-table" data-testid="role-table">
        <caption className="role-table__caption">User roles and permissions</caption>
        <thead>
          <tr>
            <th className="role-table__th role-table__th--checkbox" scope="col">
              <input
                type="checkbox"
                checked={allSelected}
                ref={(el) => {
                  if (el) el.indeterminate = someSelected;
                }}
                onChange={(e) => onSelectAll?.(e.target.checked)}
                aria-label="Select all users"
                data-testid="select-all-users"
              />
            </th>
            <th className="role-table__th" scope="col">
              <SortButton
                active={sortKey === 'email'}
                direction={sortDirection || 'asc'}
                onClick={() => handleSort('email')}
              >
                Email
              </SortButton>
            </th>
            <th className="role-table__th" scope="col">
              <SortButton
                active={sortKey === 'name'}
                direction={sortDirection || 'asc'}
                onClick={() => handleSort('name')}
              >
                Name
              </SortButton>
            </th>
            <th className="role-table__th" scope="col">
              <SortButton
                active={sortKey === 'role'}
                direction={sortDirection || 'asc'}
                onClick={() => handleSort('role')}
              >
                Role
              </SortButton>
            </th>
            <th className="role-table__th" scope="col">
              <SortButton
                active={sortKey === 'permission_count'}
                direction={sortDirection || 'asc'}
                onClick={() => handleSort('permission_count')}
              >
                Permissions
              </SortButton>
            </th>
            <th className="role-table__th" scope="col">
              <SortButton
                active={sortKey === 'created_at'}
                direction={sortDirection || 'asc'}
                onClick={() => handleSort('created_at')}
              >
                Created
              </SortButton>
            </th>
            <th className="role-table__th" scope="col">
              <span className="role-table__th-text">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody>
          {sortedUsers.map((user) => {
            const isSelected = selectedUserIds.includes(user.id);
            const isExpanded = expandedId === user.id;

            return (
              <React.Fragment key={user.id}>
                <tr
                  className={`role-table__row ${isSelected ? 'role-table__row--selected' : ''}`}
                  data-testid={`user-row-${user.id}`}
                >
                  <td className="role-table__td role-table__td--checkbox">
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onChange={(e) => {
                        onSelectUser?.(user.id, e.target.checked);
                      }}
                      aria-label={`Select ${user.email}`}
                      data-testid={`select-user-${user.id}`}
                    />
                  </td>
                  <td className="role-table__td role-table__td--text" title={user.email} data-testid={`user-email-${user.id}`}>
                    {user.email}
                  </td>
                  <td className="role-table__td role-table__td--text" title={user.name} data-testid={`user-name-${user.id}`}>
                    {user.name}
                  </td>
                  <td className="role-table__td" data-testid={`user-role-${user.id}`}>
                    <RoleBadge role={user.role} size="sm" />
                  </td>
                  <td className="role-table__td" data-testid={`user-permission-count-${user.id}`}>
                    {user.permission_count ?? 0}
                  </td>
                  <td className="role-table__td role-table__td--text" data-testid={`user-created-${user.id}`}>
                    {user.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="role-table__td role-table__td--actions" data-testid={`user-actions-${user.id}`}>
      <button
        type="button"
        onClick={() => handleToggleExpand(user.id)}
        onKeyDown={(e) => handleExpandKeyDown(e, user.id)}
        aria-expanded={isExpanded}
        aria-controls={`expand-${user.id}`}
        aria-label={isExpanded ? `Collapse ${user.email}` : `Expand ${user.email}`}
        className="role-table__expand-btn"
        id={`expand-btn-${user.id}`}
      >
        {isExpanded ? '−' : '+'}
      </button>
                  </td>
                </tr>
                {isExpanded && (
                  <tr
                    id={`expand-${user.id}`}
                    className="role-table__expand"
                    data-testid={`user-expand-${user.id}`}
                  >
                    <td colSpan={7}>
                      <div
                        className="role-table__expand-content"
                        ref={(el) => {
                          expandableRefs.current[user.id] = el as HTMLDivElement;
                        }}
                        tabIndex={-1}
                        role="region"
                        aria-labelledby={`expand-btn-${user.id}`}
                        aria-label={`Permissions for ${user.email}`}
                      >
                        <strong>Permissions:</strong>
                        {user.permissions?.length ? (
                          <ul className="role-table__permission-list">
                            {user.permissions.map((perm) => (
                              <li key={perm.id || perm.name}>{perm.name}</li>
                            ))}
                          </ul>
                        ) : (
                          <p className="role-table__empty-permissions">No permissions assigned.</p>
                        )}
                      </div>
                    </td>
                  </tr>
                )}
              </React.Fragment>
            );
          })}
        </tbody>
      </table>
    </div>
  );
};

export default RoleTable;
