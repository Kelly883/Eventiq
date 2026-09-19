import React from 'react';

const DashboardHeader = ({ user }) => {
  const firstName = user?.name?.split(' ')[0] || 'there';
  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  };

  return (
    <div className="greeting-bar">
      <div className="greeting-content">
        <h2 className="greeting-title">
          {getGreeting()}, {firstName}
        </h2>
        <p className="greeting-subtitle">
          Manage your tickets, discover upcoming events, and stay organized.
        </p>
      </div>
    </div>
  );
};

export default DashboardHeader;
