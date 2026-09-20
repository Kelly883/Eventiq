import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import { LoadingSpinner } from '../../common';
import '../../../styles/shared-pages.css';

const AdminRoleManagementPage = () => {
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchRoles = async () => {
      try {
        const res = await api.get('/admin/roles');
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
      <div className="spa-page">
        <div className="spa-container" style={{ maxWidth: '768px' }}>
          <div
            className="spa-card"
            style={{ padding: '24px', textAlign: 'center', background: '#fef2f2', borderColor: '#fecaca' }}
          >
            <h2 style={{ fontSize: '1.25rem', fontWeight: 600, color: '#991b1b', marginBottom: '8px' }}>
              Something went wrong
            </h2>
            <p style={{ color: '#b91c1c' }}>{error}</p>
            <button
              onClick={() => window.location.reload()}
              className="spa-btn"
              style={{ background: '#dc2626', color: '#fff', marginTop: '16px' }}
            >
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="spa-page">
      <div className="spa-container" style={{ maxWidth: '1024px' }}>
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginBottom: '32px',
            flexWrap: 'wrap',
            gap: '16px',
          }}
        >
          <div>
            <h1 className="spa-page__title" style={{ fontSize: '1.875rem' }}>
              Role Management
            </h1>
            <p className="spa-page__subtitle">
              Manage user roles and permissions across the platform.
            </p>
          </div>
          <Link to="/settings/permissions" className="spa-btn spa-btn--secondary">
            ← Back to Permissions
          </Link>
        </div>

        {roles.length === 0 ? (
          <div className="spa-card spa-empty">
            <div
              className="spa-empty__icon"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                width: '48px',
                height: '48px',
                borderRadius: '50%',
                background: '#eef2ff',
                color: '#4f46e5',
              }}
            >
              <svg style={{ width: '24px', height: '24px' }} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 12c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" />
              </svg>
            </div>
            <h2 className="spa-empty__title">No roles configured</h2>
            <p className="spa-empty__text">
              Role management is ready for configuration. Contact your system administrator
              to set up organizational roles and permission schemes.
            </p>
          </div>
        ) : (
          <div className="spa-card" style={{ overflow: 'hidden', padding: 0 }}>
            <table className="spa-table">
              <thead>
                <tr>
                  <th>Role</th>
                  <th>Permissions</th>
                  <th>Users</th>
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id}>
                    <td>
                      <span style={{ fontWeight: 600, color: '#333' }}>{role.name}</span>
                    </td>
                    <td style={{ color: '#666', fontSize: '14px' }}>
                      {role.permissions?.length > 0
                        ? role.permissions.map((p) => p.name).join(', ')
                        : 'No permissions assigned'}
                    </td>
                    <td style={{ color: '#666', fontSize: '14px' }}>
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
