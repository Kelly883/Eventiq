import React from 'react';

export const ModerationNotes = ({ loading, notes, onChange }) => {
  if (loading) return <div className="animate-pulse h-32 bg-gray-100 rounded" />;
  return (
    <div className="p-4 border rounded mt-4">
      <div className="text-sm font-semibold text-gray-900">Moderation Notes</div>
      <textarea
        className="mt-2 w-full border rounded px-3 py-2 text-sm"
        rows={4}
        value={notes ?? ''}
        onChange={(e) => onChange?.(e.target.value)}
      />
    </div>
  );
};

