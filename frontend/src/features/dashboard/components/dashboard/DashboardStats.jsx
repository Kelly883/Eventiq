import React from 'react';
import Icon from './Icon';

const DashboardStats = ({ stats = [], loading = false }) => {
  const defaultStats = [
    { label: 'Upcoming Events', value: '0', icon: 'calendar' },
    { label: 'Tickets', value: '0', icon: 'ticket' },
    { label: 'Orders', value: '0', icon: 'browse' },
  ];

  const displayStats = stats.length > 0 ? stats : defaultStats;

  if (loading) {
    return (
      <section className="dashboard-section" aria-label="Dashboard statistics">
        <div className="stats-grid">
          {displayStats.map((stat, i) => (
            <div key={i} className="stat-card-skeleton" aria-hidden="true" />
          ))}
        </div>
      </section>
    );
  }

  return (
    <section className="dashboard-section" aria-label="Dashboard statistics">
      <div className="stats-grid">
        {displayStats.map((stat) => (
          <div key={stat.label} className="stat-card">
            <div className="stat-icon">
              <Icon name={stat.icon} size={22} />
            </div>
            <div className="stat-info">
              <span className="stat-value">{stat.value}</span>
              <span className="stat-label">{stat.label}</span>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
};

export default DashboardStats;
