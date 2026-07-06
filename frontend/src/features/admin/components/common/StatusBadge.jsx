import React from 'react';

export const StatusBadge = ({ status }) => {
  const color = status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
  return <span className={`px-2 py-1 rounded text-xs font-medium ${color}`}>{status ?? '—'}</span>;
};

