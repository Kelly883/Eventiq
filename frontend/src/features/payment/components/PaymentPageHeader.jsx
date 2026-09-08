import React from 'react';
import { Link } from 'react-router-dom';

/**
 * Shared header for the payment settings pages: breadcrumb, back link, and
 * page title. The back link returns to the nearest settings landing page
 * so users never dead-end inside a payment section.
 */
const PaymentPageHeader = ({ title, subtitle, crumbs = [], backTo = null, backLabel = 'Back to Settings' }) => (
  <div className="mb-6">
    <nav aria-label="Breadcrumb" className="mb-3 flex flex-wrap items-center gap-1.5 text-xs text-slate-400">
      <Link to="/" className="hover:text-slate-600 transition-colors">
        Home
      </Link>
      {crumbs.map((crumb, index) => (
        <span key={index} className="flex items-center gap-1.5">
          <span aria-hidden="true">/</span>
          {crumb.to ? (
            <Link to={crumb.to} className="hover:text-indigo-600 transition-colors">
              {crumb.label}
            </Link>
          ) : (
            <span className="text-slate-500" aria-current="page">
              {crumb.label}
            </span>
          )}
        </span>
      ))}
    </nav>

    {backTo && (
      <Link
        to={backTo}
        className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors"
      >
        <span aria-hidden="true">←</span> {backLabel}
      </Link>
    )}

    <h2 className="mt-2 text-xl font-bold text-slate-900">{title}</h2>
    {subtitle && <p className="mt-1 text-sm text-slate-500">{subtitle}</p>}
  </div>
);

export default PaymentPageHeader;