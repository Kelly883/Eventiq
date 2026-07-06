import React from 'react';

export const EmptyStates = ({ title = 'Nothing here', description }) => {
  return (
    <div className="p-6 text-center">
      <div className="text-sm font-semibold text-gray-900">{title}</div>
      {description && <div className="text-sm text-gray-500 mt-1">{description}</div>}
    </div>
  );
};

