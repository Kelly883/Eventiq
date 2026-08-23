import React, { useEffect, useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { showToast } from '../../../lib/api';

const UserPermissionsPage = () => {
  const location = useLocation();
  const { user } = useAuthContext();
  const [showDeniedBanner, setShowDeniedBanner] = useState(
    Boolean(location.state?.deniedByRole)
  );

  useEffect(() => {
    if (location.state?.message) {
      showToast('Notice', location.state.message, location.state.messageType || 'warning');
    }
  }, [location.state?.message, location.state.messageType]);

  const userRoles = user?.roles?.map((r) => r.name) || [];
  const isAdmin = userRoles.includes('admin');

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        {showDeniedBanner && (
          <div
            role="alert"
            className="mb-6 flex items-start justify-between gap-4 bg-amber-50 border border-amber-200 rounded-xl p-4"
          >
            <div>
              <h2 className="text-sm font-bold text-amber-800 mb-1">
                Admin access required
              </h2>
              <p className="text-sm text-amber-700">
                The page you tried to open is only available to administrators.
                You have been taken to your permissions instead.
              </p>
            </div>
            <button
              type="button"
              onClick={() => setShowDeniedBanner(false)}
              aria-label="Dismiss message"
              className="text-amber-500 hover:text-amber-700 font-bold text-lg leading-none"
            >
              ×
            </button>
          </div>
        )}

        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Your Permissions</h1>
          <p className="mt-2 text-sm text-slate-500">
            View your current role assignments and permission levels.
          </p>
        </div>

        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
          <h2 className="text-lg font-bold text-slate-800 mb-4">Current Role</h2>
          <div className="flex items-center gap-4">
            <span className="px-4 py-2 bg-slate-100 text-slate-700 font-semibold rounded-lg">
              {userRoles.length > 0 ? userRoles.join(', ') : 'No roles assigned'}
            </span>
            {isAdmin && (
              <span className="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-full">
                Administrator
              </span>
            )}
          </div>
          <p className="mt-3 text-sm text-slate-500">
            {isAdmin
              ? 'You have full administrative access to the platform.'
              : userRoles.length === 0
                ? 'You have no roles assigned. Contact an administrator to request access.'
                : `You have the ${userRoles[0]} role. Contact an administrator to request elevated permissions if needed.`}
          </p>
        </div>

        {!isAdmin && (
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-6">
            <h3 className="text-lg font-semibold text-amber-800 mb-2">Need different access?</h3>
            <p className="text-sm text-amber-700 mb-4">
              If you need administrator access or want to request a role change,
              please contact your organization's system administrator.
            </p>
            <Link
              to="/dashboard/organizer"
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
            >
              ← Back to Dashboard
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default UserPermissionsPage;
