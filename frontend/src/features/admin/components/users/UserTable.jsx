import React from 'react';

export const UserTable = ({ loading, users, onRowClick }) => {
  if (loading) return <div className="animate-pulse h-48 bg-gray-100 rounded" />;
  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-gray-500 border-b">
          <th className="py-2 px-2">Name</th>
          <th className="py-2 px-2">Role</th>
        </tr>
      </thead>
      <tbody>
        {(users || []).length === 0 ? (
          <tr>
            <td colSpan={2} className="py-6 px-2 text-gray-500">
              No users
            </td>
          </tr>
        ) : (
          users.map((u) => (
            <tr
              key={u.id}
              className="border-b hover:bg-gray-50 cursor-pointer"
              onClick={() => onRowClick?.(u)}
            >
              <td className="py-2 px-2 font-medium text-gray-900">{u.name ?? '—'}</td>
              <td className="py-2 px-2 text-gray-600">{u.role ?? '—'}</td>
            </tr>
          ))
        )}
      </tbody>
    </table>
  );
};

