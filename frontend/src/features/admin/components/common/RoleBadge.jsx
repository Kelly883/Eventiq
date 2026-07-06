import React from 'react';

export const RoleBadge = ({ role }) => {
  const color = role === 'admin' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800';
  return <span className={`px-2 py-1 rounded text-xs font-medium ${color}`}>{role ?? '—'}</span>;
};

