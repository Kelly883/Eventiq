import React, { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { showToast } from '../../../lib/api';

const UserPermissionsPage = () => {
  const location = useLocation();

  useEffect(() => {
    if (location.state?.message) {
      showToast('Notice', location.state.message, 'warning');
    }
  }, [location.state?.message]);

  return (
    <div>
      <h1>User Permissions</h1>
    </div>
  );
};

export default UserPermissionsPage;
