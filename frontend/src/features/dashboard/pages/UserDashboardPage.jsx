import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useAuthContext } from '../../auth/context/AuthContext';
import { ticketKeys } from '../../../lib/queryKeys';
import { api } from '../../../lib/api';
import DashboardHeader from '../components/dashboard/DashboardHeader';
import DashboardStats from '../components/dashboard/DashboardStats';
import QuickActions from '../components/dashboard/QuickActions';
import UpcomingEvents from '../components/dashboard/UpcomingEvents';
import RecentTickets from '../components/dashboard/RecentTickets';
import GettingStarted from '../components/dashboard/GettingStarted';
import MobileNav from '../components/dashboard/MobileNav';
import Icon from '../components/dashboard/Icon';
import '../dashboard.css';

const UserDashboardPage = () => {
  const { user } = useAuthContext();
  const [showWelcome, setShowWelcome] = useState(true);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const { data: ticketsData, isLoading: ticketsLoading } = useQuery({
    queryKey: ticketKeys.lists(),
    queryFn: async () => {
      const response = await api.get('/tickets');
      return response.data;
    },
    staleTime: 2 * 60 * 1000,
    gcTime: 10 * 60 * 1000,
  });

  const tickets = ticketsData?.data || ticketsData || [];
  const hasTickets = Array.isArray(tickets) && tickets.length > 0;

  const quickActions = [
    {
      to: '/events',
      icon: 'browse',
      title: 'Browse Events',
      description: 'Discover upcoming events and book tickets',
    },
    {
      to: '/my-tickets',
      icon: 'ticket',
      title: 'My Tickets',
      description: 'View and manage your purchased tickets',
    },
    {
      to: '/my-tickets/status',
      icon: 'search',
      title: 'Check Ticket',
      description: 'Look up ticket status by reference code',
    },
    {
      to: '/events/calendar',
      icon: 'calendar',
      title: 'Calendar',
      description: 'View events by date',
    },
    {
      to: '/settings',
      icon: 'settings',
      title: 'Settings',
      description: 'Manage your account and preferences',
    },
  ];

  const gettingStartedSteps = [
    {
      title: 'Browse Events',
      description: 'Find events that interest you',
      completed: false,
    },
    {
      title: 'Purchase Your First Ticket',
      description: 'Book your spot at an event',
      completed: hasTickets,
    },
    {
      title: 'View Your Tickets',
      description: 'Access your tickets anytime',
      completed: hasTickets,
    },
  ];

  const mobileNavItems = [
    { to: '/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/my-tickets', label: 'My Tickets', icon: 'ticket' },
    { to: '/events', label: 'Browse Events', icon: 'browse' },
    { to: '/events/calendar', label: 'Calendar', icon: 'calendar' },
    { to: '/settings', label: 'Settings', icon: 'settings' },
  ];

  return (
    <div className="dashboard-page">
      {/* Mobile Header */}
      <header className="dashboard-mobile-header">
        <Link to="/" className="dashboard-brand" aria-label="Eventiq home">
          <span className="dashboard-brand-name">EventIQ</span>
        </Link>
        <div className="dashboard-header-actions">
          <button
            type="button"
            className="dashboard-icon-btn"
            aria-label="Notifications"
          >
            <Icon name="bell" size={22} />
          </button>
          <button
            type="button"
            className="dashboard-icon-btn"
            aria-label="Open navigation"
            onClick={() => setMobileNavOpen(true)}
          >
            <Icon name="menu" size={24} />
          </button>
        </div>
      </header>

      {/* Mobile Navigation Drawer */}
      <MobileNav
        user={user}
        items={mobileNavItems}
        isOpen={mobileNavOpen}
        onClose={() => setMobileNavOpen(false)}
      />

      <main className="dashboard-main">
        <div className="dashboard-container">
          <DashboardHeader user={user} />

          {/* Welcome Banner */}
          {showWelcome && (
            <div className="welcome-banner">
              <div className="welcome-banner-content">
                <h2 className="welcome-title">
                  Welcome to EventIQ{user?.name ? `, ${user.name.split(' ')[0]}` : ''}!
                </h2>
                <p className="welcome-text">
                  Your personal dashboard is here to help you manage tickets, discover events, and stay organized.
                </p>
                <div className="welcome-actions">
                  <Link to="/events" className="btn-primary">
                    <Icon name="browse" size={18} />
                    Explore Events
                  </Link>
                  {hasTickets && (
                    <Link to="/my-tickets" className="btn-secondary">
                      <Icon name="ticket" size={18} />
                      My Tickets ({tickets.length})
                    </Link>
                  )}
                  <button
                    type="button"
                    className="btn-ghost"
                    onClick={() => setShowWelcome(false)}
                  >
                    Maybe Later
                  </button>
                </div>
              </div>
              <button
                type="button"
                className="welcome-dismiss"
                onClick={() => setShowWelcome(false)}
                aria-label="Dismiss welcome banner"
              >
                <Icon name="close" size={20} />
              </button>
            </div>
          )}

          <DashboardStats
            stats={[
              { label: 'Upcoming Events', value: '0', icon: 'calendar' },
              { label: 'Tickets', value: String(tickets.length), icon: 'ticket' },
              { label: 'Orders', value: '0', icon: 'browse' },
            ]}
            loading={ticketsLoading}
          />

          <QuickActions
            actions={quickActions}
            onActionClick={(action) => {
              if (action.to) {
                window.location.href = action.to;
              }
            }}
          />

          <UpcomingEvents
            events={[]}
            loading={false}
            onBrowseEvents={() => {
              window.location.href = '/events';
            }}
          />

          <RecentTickets
            tickets={tickets.slice(0, 5).map((t) => ({
              id: t.id,
              eventTitle: t.event?.title || t.eventTitle || 'Event',
              eventDate: t.event?.start_datetime || t.eventDate || t.createdAt,
              status: t.status || 'confirmed',
              reference: t.reference || t.id,
            }))}
            loading={ticketsLoading}
            onViewAll={() => {
              window.location.href = '/my-tickets';
            }}
          />

          <GettingStarted
            steps={gettingStartedSteps}
            onDismiss={() => {}}
          />
        </div>
      </main>
    </div>
  );
};

export default UserDashboardPage;
