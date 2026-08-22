import React from 'react';
import { useNavigate } from 'react-router-dom';

const AccessDeniedPage = ({ title = 'Access Denied', message = 'You do not have permission to access this page.' }) => {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4">
      <div className="max-w-md w-full bg-white p-8 rounded-xl border border-red-100 shadow-sm text-center">
        <div className="flex justify-center mb-4">
          <div className="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zm.008-6.905a9 9 0 11-2.25-.241.75.75 0 00.626.825 1.5 1.5 0 10-1.5-1.5A1.5 1.5 0 0012 15z" />
            </svg>
          </div>
        </div>
        <h1 className="text-2xl font-bold text-slate-900 mb-2">{title}</h1>
        <p className="text-slate-600 mb-6">{message}</p>
        <div className="flex gap-3 justify-center">
          <button
            onClick={() => navigate(-1)}
            className="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition-colors"
          >
            ← Go Back
          </button>
          <button
            onClick={() => navigate('/dashboard/organizer')}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition-colors"
          >
            Dashboard
          </button>
        </div>
      </div>
    </div>
  );
};

export default AccessDeniedPage;
