import React from 'react';
import { Link, Route, Routes } from 'react-router-dom';
import { AdminEmailTemplateManagementPage } from '../../email-notifications/pages/AdminEmailTemplateManagementPage';
import { AdminPushTemplateManagementPage } from '../../push-notifications/components/AdminPushTemplateManagementPage';

const AdminSettingsLandingPage = () => {
  const settingsCards = [
    {
      to: '/admin/settings/email-templates',
      icon: '✉️',
      title: 'Email Templates',
      description: 'Transactional notification templates for emails sent to users.',
    },
    {
      to: '/admin/settings/push-templates',
      icon: '📱',
      title: 'Push Templates',
      description: 'Mobile and web push notification templates.',
    },
  ];

  return (
    <div className="max-w-3xl mx-auto">
      <div className="mb-8">
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          Settings
        </h1>
        <p className="mt-2 text-sm text-slate-500">
          Manage platform configuration and notification templates.
        </p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
        {settingsCards.map((card) => (
          <Link
            key={card.to}
            to={card.to}
            className="block bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all"
          >
            <div className="text-3xl mb-3">{card.icon}</div>
            <h2 className="text-lg font-semibold text-slate-800">{card.title}</h2>
            <p className="text-sm text-slate-500 mt-1">{card.description}</p>
          </Link>
        ))}
      </div>
    </div>
  );
};

const AdminSettingsPage = () => {
  return (
    <Routes>
      <Route index element={<AdminSettingsLandingPage />} />
      <Route path="email-templates/*" element={<AdminEmailTemplateManagementPage />} />
      <Route path="push-templates/*" element={<AdminPushTemplateManagementPage />} />
    </Routes>
  );
};

export default AdminSettingsPage;
