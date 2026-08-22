import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import { LoadingSpinner } from '../../common';

const AdminRoleManagementPage = () => {
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchRoles = async () => {
      try {
        const res = await api.get('/api/admin/roles');
        setRoles(res.data.data || res.data || []);
        setError(null);
      } catch (err) {
        const status = err?.response?.status;
        if (status === 401) {
          showToast('Session expired', 'Please log in again to continue.', 'warning');
        } else if (status === 403) {
          showToast('Access Denied', 'You do not have admin permissions.', 'error');
        } else {
          setError('Failed to load roles. Please try again later.');
          showToast('Error', 'Could not load roles. Please try again later.', 'error');
        }
      } finally {
        setLoading(false);
      }
    };

    fetchRoles();
  }, []);

  if (loading) {
    return <LoadingSpinner message="Loading roles..." />;
  }

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-3xl">
          <div className="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <h2 className="text-xl font-semibold text-red-800 mb-2">Something went wrong</h2>
            <p className="text-red-700">{error}</p>
            <button
              onClick={() => window.location.reload()}
              className="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-5xl">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Role Management</h1>
            <p className="mt-2 text-sm text-slate-500">
              Manage user roles and permissions across the platform.
            </p>
          </div>
          <Link
            to="/settings/permissions"
            className="px-4 py-2 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors"
          >
            ← Back to Permissions
          </Link>
        </div>

        {roles.length === 0 ? (
          <div className="bg-white rounded-xl border border-slate-200 p-8 text-center">
            <div className="flex justify-center mb-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" />
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 12c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" />
                </svg>
              </div>
            </div>
            <h2 className="text-xl font-semibold text-slate-800 mb-2">No roles configured</h2>
            <p className="text-slate-500">
              Role management is ready for configuration. Contact your system administrator
              to set up organizational roles and permission schemes.
            </p>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table className="w-full">
              <thead className="bg-slate-50 border-b">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Role</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Permissions</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Users</th>
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id} className="border-b last:border-0">
                    <td className="px-6 py-4">
                      <span className="font-semibold text-slate-900">{role.name}</span>
                    </td>
                    <td className="px-6 py-4 text-sm text-slate-600">
                      {role.permissions?.length > 0
                        ? role.permissions.map((p) => p.name).join(', ')
                        : 'No permissions assigned'}
                    </td>
                    <td className="px-6 py-4 text-sm text-slate-600">
                      {role.users_count || 0}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminRoleManagementPage;
