import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AdminEmailTemplateManagementPage } from '../../email-notifications/pages/AdminEmailTemplateManagementPage';
import { AdminPushTemplateManagementPage } from '../../push-notifications/components/AdminPushTemplateManagementPage';

const AdminSettingsPage = () => {
  return (
    <Routes>
      <Route index element={<Navigate to="./email-templates" replace />} />
      <Route path="email-templates/*" element={<AdminEmailTemplateManagementPage />} />
      <Route path="push-templates/*" element={<AdminPushTemplateManagementPage />} />
    </Routes>
  );
};

export default AdminSettingsPage;