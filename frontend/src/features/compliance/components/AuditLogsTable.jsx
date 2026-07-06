import React from 'react';

const AuditLogsTable = ({ loading, logs, selectedIds, setSelectedIds }) => {
  if (loading) return <div className="animate-pulse h-64 bg-gray-100" />;

  const rows = logs || [];
  const isChecked = (id) => (selectedIds || []).includes(id);

  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-gray-500 border-b">
          <th className="py-3 px-2 w-12">Sel</th>
          <th className="py-3 px-2">Action</th>
          <th className="py-3 px-2">Entity</th>
          <th className="py-3 px-2">When</th>
        </tr>
      </thead>
      <tbody>
        {rows.length === 0 ? (
          <tr>
            <td colSpan={4} className="py-10 px-2 text-center text-gray-500">No audit logs</td>
          </tr>
        ) : (
          rows.map((l) => (
            <tr key={l.id} className="border-b hover:bg-gray-50">
              <td className="py-2 px-2">
                <input
                  type="checkbox"
                  checked={isChecked(l.id)}
                  onChange={(e) => {
                    const next = new Set(selectedIds || []);
                    if (e.target.checked) next.add(l.id);
                    else next.delete(l.id);
                    setSelectedIds?.(Array.from(next));
                  }}
                />
              </td>
              <td className="py-2 px-2 font-medium text-gray-900">{l.action ?? '—'}</td>
              <td className="py-2 px-2 text-gray-600">{l.entity ?? '—'}</td>
              <td className="py-2 px-2 text-gray-500">{l.created_at ?? '—'}</td>
            </tr>
          ))
        )}
      </tbody>
    </table>
  );
};

export { AuditLogsTable };


