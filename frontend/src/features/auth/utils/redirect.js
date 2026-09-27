// Central, single source of truth for post-login redirects.
//
// Used by:
//  - LoginPage  (after successful login)
//  - App.jsx    (deep-link recovery effect)
//
// The `from` value may be a plain path string OR the `location` object that
// ProtectedRoute stores in `state.from` — normalize both here so the two
// consumers can never drift apart.

export function normalizeFromPath(from) {
  if (!from) return null;
  if (typeof from === 'string') return from;
  if (from.pathname) return `${from.pathname}${from.search || ''}${from.hash || ''}`;
  return null;
}

import { getUserRoles } from '../../../lib/authRoles';

// Given the requested path and the current user's roles, return the safest
// landing path (user can never be tracked into a route they lack permission for).
export function safeRedirectPath(from, user, fallback = '/dashboard') {
  const target = normalizeFromPath(from) || fallback;
  const roles = getUserRoles(user);

  if (target === '/dashboard/organizer' && !roles.includes('organizer')) {
    return '/dashboard';
  }
  if (target.startsWith('/admin/') && !roles.includes('admin')) {
    return '/access-denied';
  }
  if (target.startsWith('/organizer/') && !roles.includes('organizer')) {
    return '/access-denied';
  }
  return target;
}

// Role-aware default landing page (used when there's no saved `from`).
export function defaultRedirect(user) {
  const roles = getUserRoles(user);
  if (roles.includes('organizer')) return '/dashboard/organizer';
  if (roles.includes('admin')) return '/admin';
  return '/dashboard';
}
