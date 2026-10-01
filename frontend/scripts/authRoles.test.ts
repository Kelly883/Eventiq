/* Unit tests for getUserRoles() — run with:
 *   node --import tsx --test scripts/authRoles.test.ts
 * (tsx ships with the repo; node:test is built in.)
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import { getUserRoles } from '../src/lib/authRoles';

test('returns [] for null/undefined user', () => {
  assert.deepEqual(getUserRoles(null), []);
  assert.deepEqual(getUserRoles(undefined), []);
});

test('reads legacy `role` string (login payload without pivot)', () => {
  // The exact shape the render-admin migration + old seeders produce:
  // role set, role_id null, roles: [] — this was the redirect bug.
  assert.deepEqual(getUserRoles({ role: 'admin', roles: [] }), ['admin']);
});

test('reads roleRelation.name', () => {
  assert.deepEqual(
    getUserRoles({ roleRelation: { name: 'organizer' }, roles: [] }),
    ['organizer'],
  );
});

test('reads roles pivot objects', () => {
  assert.deepEqual(
    getUserRoles({ roles: [{ id: 1, name: 'admin' }, { id: 2, name: 'organizer' }] }),
    ['admin', 'organizer'],
  );
});

test('dedupes + lowercases across all three sources', () => {
  const roles = getUserRoles({
    role: 'Admin',
    roleRelation: { name: 'admin' },
    roles: [{ name: 'admin' }, { name: 'Organizer' }],
  });
  assert.deepEqual(roles.sort(), ['admin', 'organizer']);
});

test('ignores non-string and unnamed entries', () => {
  assert.deepEqual(getUserRoles({ role: 42, roles: [{ id: 1 }, { name: null }] }), []);
});