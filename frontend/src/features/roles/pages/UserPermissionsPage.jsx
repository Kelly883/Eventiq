import React, { useEffect, useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api, showToast } from '../../../lib/api';

const ACCESS_OPTIONS = [
  { value: 'admin-access', label: 'Admin access' },
  { value: 'organizer-access', label: 'Organizer access' },
];

const UserPermissionsPage = () => {
  const location = useLocation();
  const { user } = useAuthContext();
  const [showDeniedBanner, setShowDeniedBanner] = useState(
    Boolean(location.state?.deniedByRole)
  );

  // Access request form state
  const [requestedPermission, setRequestedPermission] = useState('admin-access');
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [requestStatus, setRequestStatus] = useState(null); // 'submitted' | 'pending' | null
  const [statusMessage, setStatusMessage] = useState('');

  useEffect(() => {
    if (location.state?.message) {
      showToast('Notice', location.state.message, location.state.messageType || 'warning');
    }
  }, [location.state?.message, location.state.messageType]);

  const userRoles = user?.roles?.map((r) => r.name) || [];
  const isAdmin = userRoles.includes('admin');

  const handleAccessRequest = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setStatusMessage('');

    try {
      await api.post('/permissions/request', {
        requestedPermission,
        reason: reason.trim(),
      });
      setRequestStatus('submitted');
      setReason('');
      showToast('Request Sent', 'Your access request has been submitted for review.', 'success');
    } catch (err) {
      if (err?.response?.status === 409) {
        setRequestStatus('pending');
        showToast('Already Pending', err?.response?.data?.message || 'A request is already under review.', 'info');
      } else {
        const msg =
          err?.response?.data?.errors?.reason?.[0] ||
          err?.response?.data?.message ||
          'Could not submit your request. Please try again.';
        setStatusMessage(msg);
        showToast('Request Failed', msg, 'error');
      }
    } finally {
      setSubmitting(false);
    }
  };

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
                {location.state?.attemptedPath
                  ? `The page ${location.state.attemptedPath} is only available to administrators.`
                  : 'That page is only available to administrators.'}{' '}
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

            {requestStatus === 'submitted' ? (
              <div className="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                Your request has been submitted. An administrator will review it
                soon — your roles will update here automatically once approved.
              </div>
            ) : requestStatus === 'pending' ? (
              <div className="text-sm text-sky-700 bg-sky-50 border border-sky-200 rounded-lg p-4">
                You already have a request under review. Administrators have been
                notified — no need to submit again.
              </div>
            ) : (
              <>
                <p className="text-sm text-amber-700 mb-4">
                  Submit a request and your platform administrator will review
                  it. Once they approve, your access updates here automatically.
                </p>
                <form onSubmit={handleAccessRequest} className="space-y-4">
                  <div>
                    <label htmlFor="access-level" className="block text-sm font-medium text-slate-900 mb-1">
                      Access Level
                    </label>
                    <select
                      id="access-level"
                      value={requestedPermission}
                      onChange={(e) => setRequestedPermission(e.target.value)}
                      className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    >
                      {ACCESS_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label htmlFor="access-reason" className="block text-sm font-medium text-slate-900 mb-1">
                      Why do you need this? <span className="text-slate-400 font-normal">(min. 10 characters)</span>
                    </label>
                    <textarea
                      id="access-reason"
                      value={reason}
                      onChange={(e) => setReason(e.target.value)}
                      required
                      minLength={10}
                      maxLength={1000}
                      rows={3}
                      placeholder="e.g., Our team admin left and I need to manage event roles."
                      className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                  </div>
                  {statusMessage && (
                    <p role="alert" className="text-sm text-red-600">{statusMessage}</p>
                  )}
                  <div className="flex items-center gap-3">
                    <button
                      type="submit"
                      disabled={submitting}
                      className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                    >
                      {submitting ? 'Submitting…' : 'Submit Request'}
                    </button>
                    <Link
                      to="/dashboard/organizer"
                      className="text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors"
                    >
                      ← Back to Dashboard
                    </Link>
                  </div>
                </form>
              </>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default UserPermissionsPage;
