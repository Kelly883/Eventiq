import React from 'react';
import './RolesComponents.css';
import type { RoleColor } from '../types';

const ROLE_COLORS: Record<string, RoleColor> = {
  admin: { bg: '#fef2f2', text: '#dc2626', border: '#fecaca' },
  organizer: { bg: '#eff6ff', text: '#2563eb', border: '#bfdbfe' },
  attendee: { bg: '#f0fdf4', text: '#16a34a', border: '#bbf7d0' },
  support: { bg: '#fff7ed', text: '#ea580c', border: '#fed7aa' },
};

const DEFAULT_COLOR: RoleColor = { bg: '#f9fafb', text: '#374151', border: '#e5e7eb' };

export const getRoleColor = (role: string): RoleColor => {
  const normalized = String(role || '').toLowerCase();
  return ROLE_COLORS[normalized] || DEFAULT_COLOR;
};

export interface RoleBadgeProps {
  role: string;
  size?: 'sm' | 'md' | 'lg';
}

const RoleBadge: React.FC<RoleBadgeProps> = ({ role, size = 'md' }) => {
  const color = getRoleColor(role);
  const normalizedRole = String(role || '').toLowerCase();
  const label = role
    ? String(role)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
    : 'Unknown';

  return (
    <span
      className={`role-badge role-badge--${size}`}
      style={{
        backgroundColor: color.bg,
        color: color.text,
        borderColor: color.border,
      }}
      data-testid={`role-badge-${normalizedRole}`}
      aria-label={`Role: ${label}`}
    >
      {label}
    </span>
  );
};

export default RoleBadge;
