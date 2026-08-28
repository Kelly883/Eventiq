import React from 'react';
import { Outlet } from 'react-router-dom';
import { useAuthContext } from '../../../auth/context/AuthContext';

export default function VenueStaffLayout() {
  return (
    <div className="min-h-screen bg-slate-50">
      <main className="mx-auto max-w-7xl p-6 md:p-10">
        <Outlet />
      </main>
    </div>
  );
}
