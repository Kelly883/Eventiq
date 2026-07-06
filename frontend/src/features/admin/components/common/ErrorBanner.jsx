import React from 'react';

export const ErrorBanner = ({ error }) => {
  if (!error) return null;
  return <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>;
};

