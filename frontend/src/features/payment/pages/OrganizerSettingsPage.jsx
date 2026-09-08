import React from 'react';
import { Link } from 'react-router-dom';
import PaymentPageHeader from '../components/PaymentPageHeader';

const sections = [
  {
    to: '/organizer/settings/payments',
    title: 'Payment Settings',
    icon: '💳',
    description:
      'See your Paystack and Flutterwave gateway status, subaccount configuration, and saved payout methods.',
    cta: 'Open Payment Settings',
  },
  {
    to: '/organizer/payouts',
    title: 'Payouts & Settlements',
    icon: '💰',
    description: 'Review your payout history, summaries, and settlement calculations.',
    cta: 'View Payouts',
  },
  {
    to: '/my/profile',
    title: 'Organizer Profile',
    icon: '👤',
    description: 'Update your public profile, contact details, and notification preferences.',
    cta: 'Edit Profile',
  },
];

const OrganizerSettingsPage = () => {
  return (
    <div>
      <PaymentPageHeader
        crumbs={[{ label: 'Organizer Settings' }]}
        title="Overview"
        subtitle="Choose a section to manage."
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {sections.map((section) => (
          <Link
            key={section.to}
            to={section.to}
            className="group flex flex-col rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md"
          >
            <span className="text-2xl">{section.icon}</span>
            <h3 className="mt-3 text-base font-semibold text-slate-900">{section.title}</h3>
            <p className="mt-1 text-sm text-slate-500 flex-1">{section.description}</p>
            <span className="mt-4 text-sm font-semibold text-indigo-600 group-hover:text-indigo-700">
              {section.cta} →
            </span>
          </Link>
        ))}
      </div>
    </div>
  );
};

export default OrganizerSettingsPage;