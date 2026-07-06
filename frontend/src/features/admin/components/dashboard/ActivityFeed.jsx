import React from 'react';

export const ActivityFeed = ({ loading, data }) => {
  if (loading) return <div className="animate-pulse h-32 bg-gray-100 rounded" />;
  return (
    <div className="p-4">
      <h2 className="font-semibold text-gray-900">Activity</h2>
      <div className="mt-2 text-sm text-gray-500">{(data && data.length) ? '' : 'No activity'}</div>
    </div>
  );
};

