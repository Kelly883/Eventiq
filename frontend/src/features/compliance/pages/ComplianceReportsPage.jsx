import React from 'react';
import { useComplianceReports } from '../hooks/useComplianceReports';
import { ReportGenerator, ComplianceChecklist, ExportButton } from '../components';

const ComplianceReportsPage = () => {
  const { loading, error, reports, checklist, generateReport, selectedReportId, setSelectedReportId, refetch } =
    useComplianceReports();

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Compliance Reports</h1>
        <ExportButton filters={{}} onExport={refetch} />
      </div>

      {error && <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>}

      <ReportGenerator
        loading={loading}
        reports={reports}
        selectedReportId={selectedReportId}
        onSelect={setSelectedReportId}
        onGenerate={(id) => generateReport(id)}
      />

      <ComplianceChecklist loading={loading} checklist={checklist} />
    </div>
  );
};

export default ComplianceReportsPage;

