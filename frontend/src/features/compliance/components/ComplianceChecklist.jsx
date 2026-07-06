import React from 'react';

const ComplianceChecklist = ({ loading, checklist }) => {
  if (loading) return <div className="animate-pulse h-40 bg-gray-100 rounded" />;

  const items = checklist || [];
  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="text-sm font-semibold text-gray-900">Compliance checklist</div>
      <div className="mt-3 space-y-2">
        {items.length === 0 ? (
          <div className="text-sm text-gray-500">No checklist items</div>
        ) : (
          items.map((it, idx) => (
            <div key={it.id ?? idx} className="flex items-center justify-between text-sm">
              <div className="text-gray-900">{it.title ?? 'Item'}</div>
              <div className={it.done ? 'text-green-700' : 'text-gray-500'}>{it.done ? 'Done' : 'Pending'}</div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export { ComplianceChecklist };

