import React from 'react';

const AdjustInventoryModal = ({ eventId, onClose }) => {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-bold text-slate-900">Adjust Inventory</h2>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-slate-600 text-xl font-bold"
          >
            ×
          </button>
        </div>
        <p className="text-sm text-slate-500 mb-4">Event ID: {eventId}</p>
        <p className="text-sm text-slate-600">Inventory adjustment form would go here.</p>
        <div className="mt-6 flex justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50"
          >
            Cancel
          </button>
          <button
            onClick={() => alert('Adjustment saved')}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-bold shadow-sm hover:bg-indigo-700"
          >
            Save Adjustment
          </button>
        </div>
      </div>
    </div>
  );
};

export default AdjustInventoryModal;
