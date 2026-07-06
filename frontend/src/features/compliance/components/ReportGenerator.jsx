import React from 'react';

const ReportGenerator = ({ loading, reports, selectedReportId, onSelect, onGenerate }) => {
  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
          <div className="text-sm font-semibold text-gray-900">Generate a report</div>
          <div className="text-sm text-gray-500 mt-1">Async generation + export scaffold</div>
        </div>

        <div className="flex gap-2 flex-wrap">
          <select
            className="border rounded px-3 py-2 text-sm"
            value={selectedReportId ?? ''}
            onChange={(e) => onSelect?.(e.target.value)}
            disabled={loading}
          >
            {(reports || []).map((r) => (
              <option key={r.id ?? r.code ?? r.name} value={r.id ?? r.code ?? r.name}>
                {r.name ?? r.code ?? 'Report'}
              </option>
            ))}
          </select>
          <button
            className="px-3 py-2 rounded bg-gray-900 text-white text-sm disabled:opacity-50"
            disabled={loading}
            onClick={() => onGenerate?.(selectedReportId)}
          >
            {loading ? 'Generating…' : 'Generate'}
          </button>
        </div>
      </div>
    </div>
  );
};

export { ReportGenerator };

