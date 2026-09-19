import React from 'react';
import Icon from './Icon';

const DashboardHeader = ({ user }) => {
  const firstName = user?.name?.split(' ')[0] || 'there';
  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  };

  return (
    <div className="dashboard-header">
      <h1 className="dashboard-title">
        {getGreeting()}, {firstName}
      </h1>
      <p className="dashboard-subtitle">
        Manage your tickets, discover upcoming events, and stay organized.
      </p>
    </div>
  );
};

export default DashboardHeader;
