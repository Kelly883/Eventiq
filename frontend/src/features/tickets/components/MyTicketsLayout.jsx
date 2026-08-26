import React from 'react';
import { Link, Outlet, useLocation, useParams } from 'react-router-dom';

const MyTicketsLayout = () => {
  const location = useLocation();
  const { ticketId } = useParams();

  const isDetailView = location.pathname.includes('/my-tickets/') && ticketId;
  const isStatusView = location.pathname.endsWith('/status');
  const isDeliveryView = location.pathname.endsWith('/delivery');

  const getPageTitle = () => {
    if (isDeliveryView) return 'Delivery Status';
    if (isStatusView) return 'Ticket Status';
    if (isDetailView) return 'Ticket Details';
    return 'My Tickets';
  };

  const getBreadcrumbs = () => {
    const crumbs = [{ to: '/my-tickets', label: 'My Tickets' }];

    if (isDetailView || isStatusView || isDeliveryView) {
      if (isStatusView && !ticketId) {
        crumbs.push({ to: null, label: 'Status Lookup' });
      } else {
        crumbs.push({
          to: `/my-tickets/${ticketId}`,
          label: `Ticket ${ticketId}`,
        });
        if (isStatusView) {
          crumbs.push({ to: null, label: 'Status' });
        } else if (isDeliveryView) {
          crumbs.push({ to: null, label: 'Delivery' });
        }
      }
    }

    return crumbs;
  };

  const breadcrumbs = getBreadcrumbs();

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-4xl">
        {/* Breadcrumbs */}
        <nav className="mb-4 flex items-center gap-2 text-sm" aria-label="Breadcrumb">
          {breadcrumbs.map((crumb, index) => (
            <React.Fragment key={index}>
              {index > 0 && <span className="text-slate-400">/</span>}
              {crumb.to ? (
                <Link
                  to={crumb.to}
                  className="text-slate-500 hover:text-slate-700 transition-colors"
                >
                  {crumb.label}
                </Link>
              ) : (
                <span className="text-slate-900 font-medium">{crumb.label}</span>
              )}
            </React.Fragment>
          ))}
        </nav>

        {/* Page Header */}
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            {getPageTitle()}
          </h1>
          {isDetailView && (
            <Link
              to="/my-tickets"
              className="px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
            >
              ← All Tickets
            </Link>
          )}
        </div>

        {/* Quick Actions for Detail Views */}
        {isDetailView && !isStatusView && !isDeliveryView && (
          <div className="mb-6 flex flex-wrap gap-2">
            <Link
              to={`/my-tickets/${ticketId}/status`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors"
            >
              🔍 View Status
            </Link>
            <Link
              to={`/my-tickets/${ticketId}/delivery`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors"
            >
              📦 Delivery Status
            </Link>
          </div>
        )}

        {/* Main Content */}
        <Outlet />
      </div>
    </div>
  );
};

export default MyTicketsLayout;
