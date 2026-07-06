import React from 'react';

export const NavigationTiles = () => {
  const tiles = [
    { label: 'Dashboard', href: '#' },
    { label: 'Users', href: '#' },
    { label: 'Moderation', href: '#' },
    { label: 'Reconciliation', href: '#' },
  ];

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
      {tiles.map((t) => (
        <a
          key={t.label}
          href={t.href}
          className="p-3 rounded border hover:bg-gray-50 text-sm font-medium text-gray-900 text-center"
        >
          {t.label}
        </a>
      ))}
    </div>
  );
};

