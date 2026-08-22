import React from 'react';

const LoadingSpinner = ({ message = 'Loading...' }) => {
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="flex flex-col items-center gap-4">
        <div className="h-12 w-12 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
        <p className="text-slate-600 text-sm">{message}</p>
      </div>
    </div>
  );
};

export default LoadingSpinner;
