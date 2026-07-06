import React from 'react';

export const UserDetailsPanel = ({ user, auditLogs, loading }) => {
  if (loading) return <div className="animate-pulse h-40 bg-gray-100 rounded" />;
  if (!user) return <div className="text-sm text-gray-500">Select a user</div>;

  return (
    <div className="p-4 border rounded">
      <div className="font-semibold text-gray-900">{user.name ?? 'User'}</div>
      <div className="text-sm text-gray-600 mt-1">Role: {user.role ?? '—'}</div>
      <div className="mt-4">
        <div className="text-sm font-semibold text-gray-900">Audit Logs</div>
        <div className="text-sm text-gray-500 mt-1">{(auditLogs || []).length ? '' : '—'}</div>
      </div>
    </div>
  );
};

