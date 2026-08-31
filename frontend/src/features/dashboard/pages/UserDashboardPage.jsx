import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { useAuthContext } from '../../auth/context/AuthContext';
import { ticketKeys } from '../../../lib/queryKeys';
import { api } from '../../../lib/api';

const UserDashboardPage = () => {
  const { user } = useAuthContext();
  const [showWelcome, setShowWelcome] = useState(true);

  const { data: ticketsData } = useQuery(
    ticketKeys.lists(),
    async () => {
      const response = await api.get('/tickets');
      return response.data;
    },
    {
      staleTime: 2 * 60 * 1000,
      cacheTime: 10 * 60 * 1000,
    }
  );

  const tickets = ticketsData?.data || ticketsData || [];
  const hasTickets = Array.isArray(tickets) && tickets.length > 0;

  const quickActions = [
    {
      to: '/events',
      icon: '📋',
      title: 'Browse Events',
      description: 'Discover upcoming events and book tickets',
    },
    {
      to: '/my-tickets',
      icon: '🎫',
      title: 'My Tickets',
      description: 'View and manage your purchased tickets',
    },
    {
      to: '/my-tickets/status',
      icon: '🔍',
      title: 'Check Ticket',
      description: 'Look up ticket status by reference code',
    },
    {
      to: '/settings',
      icon: '⚙️',
      title: 'Settings',
      description: 'Manage your account and preferences',
    },
  ];

  return (
    <div>
      {/* Welcome Banner for New Users */}
      {showWelcome && (
        <div className="bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-xl p-6 shadow-sm mb-6 text-white relative overflow-hidden">
          <div className="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
          <div className="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full"></div>
          <div className="relative">
            <h2 className="text-2xl font-bold mb-2">
              Welcome to Eventiq{user?.name ? `, ${user.name}` : ''}! 🎉
            </h2>
            <p className="text-indigo-100 mb-4 max-w-lg">
              Your personal dashboard is here to help you manage tickets, discover events, and stay organized.
              Let's get you started!
            </p>
            <div className="flex flex-wrap gap-3">
              <Link
                to="/events"
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-indigo-700 text-sm font-semibold hover:bg-indigo-50 transition-colors"
              >
                📋 Explore Events
              </Link>
              {hasTickets && (
                <Link
                  to="/my-tickets"
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-indigo-700 text-sm font-semibold hover:bg-indigo-50 transition-colors"
                >
                  🎫 My Tickets ({tickets.length})
                </Link>
              )}
              <Link
                to="/my-tickets"
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-700/50 text-white text-sm font-medium hover:bg-indigo-700/70 transition-colors border border-indigo-500/50"
              >
                🎫 View My Tickets
              </Link>
              <button
                onClick={() => setShowWelcome(false)}
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-700/30 text-white/80 text-sm font-medium hover:bg-indigo-700/50 transition-colors"
              >
                Maybe Later
              </button>
            </div>
          </div>
          <button
            onClick={() => setShowWelcome(false)}
            className="absolute top-3 right-3 text-white/70 hover:text-white transition-colors"
            aria-label="Dismiss welcome banner"
          >
            ✕
          </button>
        </div>
      )}

      {/* Quick Actions */}
      <div className="mb-6">
        <h3 className="text-lg font-semibold text-slate-800 mb-4">Quick Actions</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {quickActions.map((action) => (
            <Link
              key={action.to}
              to={action.to}
              className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all group"
            >
              <div className="flex items-start gap-4">
                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-2xl group-hover:bg-indigo-100 transition-colors">
                  {action.icon}
                </div>
                <div>
                  <h4 className="font-semibold text-slate-900 group-hover:text-indigo-700 transition-colors">
                    {action.title}
                  </h4>
                  <p className="text-sm text-slate-500 mt-1">{action.description}</p>
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>

      {/* Getting Started */}
      <div className="bg-gradient-to-br from-indigo-50 to-slate-50 rounded-xl border border-indigo-100 p-6">
        <h3 className="text-lg font-semibold text-slate-800 mb-3">Getting Started</h3>
        <ul className="space-y-3">
          <li className="flex items-start gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">1</span>
            <div>
              <p className="text-sm font-medium text-slate-800">Browse Events</p>
              <p className="text-xs text-slate-500">Find events that interest you and purchase tickets</p>
            </div>
          </li>
          <li className="flex items-start gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">2</span>
            <div>
              <p className="text-sm font-medium text-slate-800">Manage Tickets</p>
              <p className="text-xs text-slate-500">View your tickets, check status, and track delivery</p>
            </div>
          </li>
          <li className="flex items-start gap-3">
            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">3</span>
            <div>
              <p className="text-sm font-medium text-slate-800">Stay Organized</p>
              <p className="text-xs text-slate-500">Use your dashboard to keep track of all your events</p>
            </div>
          </li>
        </ul>
      </div>
    </div>
  );
};

export default UserDashboardPage;
