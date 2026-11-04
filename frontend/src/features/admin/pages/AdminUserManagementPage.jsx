import React from 'react';
import { useUserManagement } from '../hooks/useUserManagement';
import { UserTable, UserDetailsPanel, BulkActionBar, UserFilters } from '../components/users';
import { api } from '../../../lib/api';
import '../../../styles/shared-pages.css';

const AdminUserManagementPage = () => {
  const {
    loading,
    error,
    users,
    selectedUser,
    filters,
    setFilters,
    bulkAction,
    setSelectedUser,
    auditLogs,
  } = useUserManagement();
  const [adminForm, setAdminForm] = React.useState({
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
  });
  const [adminFormLoading, setAdminFormLoading] = React.useState(false);
  const [adminFormError, setAdminFormError] = React.useState('');
  const [adminFormSuccess, setAdminFormSuccess] = React.useState('');

  const handleAdminAccountSubmit = async (event) => {
    event.preventDefault();
    setAdminFormError('');
    setAdminFormSuccess('');

    if (adminForm.password !== adminForm.passwordConfirmation) {
      setAdminFormError('Passwords do not match.');
      return;
    }

    setAdminFormLoading(true);
    try {
      const response = await api.post('/admin/users/admin-accounts', {
        name: adminForm.name,
        email: adminForm.email,
        password: adminForm.password,
        password_confirmation: adminForm.passwordConfirmation,
      });
      setAdminFormSuccess(`Administrator account created for ${response.data.user.email}.`);
      setAdminForm({ name: '', email: '', password: '', passwordConfirmation: '' });
    } catch (error) {
      setAdminFormError(
        error?.response?.data?.errors?.name?.[0] ||
        error?.response?.data?.errors?.email?.[0] ||
        error?.response?.data?.errors?.password?.[0] ||
        error?.response?.data?.errors?.password_confirmation?.[0] ||
        error?.response?.data?.message ||
        'Could not create the administrator account. Please try again.'
      );
    } finally {
      setAdminFormLoading(false);
    }
  };

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">User Management</h1>
        </div>

        <section className="spa-card spa-card--padded" aria-labelledby="create-admin-heading" style={{ marginBottom: '24px' }}>
          <h2 id="create-admin-heading" className="spa-page__title" style={{ fontSize: '1.125rem', marginBottom: '8px' }}>
            Create an administrator account
          </h2>
          <p className="spa-page__subtitle" style={{ marginBottom: '20px' }}>
            Add another platform administrator. Share the initial password with them through a secure channel.
          </p>
          <form onSubmit={handleAdminAccountSubmit} aria-busy={adminFormLoading}>
            <div className="spa-grid spa-grid--2">
              <div className="spa-field">
                <label htmlFor="new-admin-name">Full name</label>
                <input
                  id="new-admin-name"
                  className="spa-input"
                  autoComplete="name"
                  required
                  minLength={2}
                  maxLength={255}
                  disabled={adminFormLoading}
                  value={adminForm.name}
                  onChange={(event) => setAdminForm({ ...adminForm, name: event.target.value })}
                />
              </div>
              <div className="spa-field">
                <label htmlFor="new-admin-email">Email address</label>
                <input
                  id="new-admin-email"
                  className="spa-input"
                  type="email"
                  autoComplete="email"
                  required
                  maxLength={255}
                  disabled={adminFormLoading}
                  value={adminForm.email}
                  onChange={(event) => setAdminForm({ ...adminForm, email: event.target.value })}
                />
              </div>
              <div className="spa-field">
                <label htmlFor="new-admin-password">Initial password</label>
                <input
                  id="new-admin-password"
                  className="spa-input"
                  type="password"
                  autoComplete="new-password"
                  required
                  minLength={8}
                  disabled={adminFormLoading}
                  value={adminForm.password}
                  onChange={(event) => setAdminForm({ ...adminForm, password: event.target.value })}
                />
              </div>
              <div className="spa-field">
                <label htmlFor="new-admin-password-confirmation">Confirm initial password</label>
                <input
                  id="new-admin-password-confirmation"
                  className="spa-input"
                  type="password"
                  autoComplete="new-password"
                  required
                  minLength={8}
                  disabled={adminFormLoading}
                  value={adminForm.passwordConfirmation}
                  onChange={(event) => setAdminForm({ ...adminForm, passwordConfirmation: event.target.value })}
                />
              </div>
            </div>
            {adminFormError && <p className="spa-alert spa-alert--error" role="alert">{adminFormError}</p>}
            {adminFormSuccess && <p className="spa-alert spa-alert--success" role="status">{adminFormSuccess}</p>}
            <button type="submit" className="spa-btn spa-btn--primary" disabled={adminFormLoading}>
              {adminFormLoading ? 'Creating account…' : 'Create administrator'}
            </button>
          </form>
        </section>

        <UserFilters filters={filters} onFiltersChange={setFilters} />

        {error && (
          <div className="spa-alert spa-alert--error">{error}</div>
        )}

        <div className="spa-card">
          <div style={{ padding: '16px', borderBottom: '1px solid #E3E4E6' }}>
            <BulkActionBar loading={loading} onAction={bulkAction} />
          </div>

          <div className="spa-grid spa-grid--3" style={{ padding: '16px' }}>
            <div style={{ gridColumn: 'span 2' }}>
              <UserTable
                loading={loading}
                users={users}
                onRowClick={(u) => setSelectedUser(u)}
              />
            </div>
            <div>
              <UserDetailsPanel user={selectedUser} auditLogs={auditLogs} loading={loading} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminUserManagementPage;
