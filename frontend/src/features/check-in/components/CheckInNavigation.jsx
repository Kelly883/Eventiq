import { NavLink } from 'react-router-dom';

const CHECK_IN_TABS = [
  { to: '/check-in', label: 'Scan / Check-In', end: true },
  { to: '/check-in/search', label: 'Search' },
  { to: '/check-in/stats', label: 'Statistics' },
  { to: '/check-in/export', label: 'Export' },
  { to: '/check-in/history', label: 'History' },
];

const CheckInNavigation = ({ eventId }) => {
  const withEventId = (path) => (eventId ? `${path}?eventId=${eventId}` : path);

  return (
    <nav aria-label="Check-in sections" className="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
      {CHECK_IN_TABS.map(({ to, label, end }) => (
        <NavLink
          key={to}
          to={withEventId(to)}
          end={end}
          className={({ isActive }) =>
            `px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              isActive
                ? 'bg-indigo-600 text-white'
                : 'text-slate-600 hover:bg-slate-100'
            }`
          }
        >
          {label}
        </NavLink>
      ))}
    </nav>
  );
};

export default CheckInNavigation;
