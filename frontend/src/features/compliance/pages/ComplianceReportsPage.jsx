import React from 'react';
import { useComplianceReports } from '../hooks/useComplianceReports';
import { ReportGenerator, ComplianceChecklist, ExportButton } from '../components';
import '../../../styles/shared-pages.css';

const ComplianceReportsPage = () => {
  const { loading, error, reports, checklist, generateReport, selectedReportId, setSelectedReportId, refetch } =
    useComplianceReports();

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">Compliance Reports</h1>
          <ExportButton filters={{}} onExport={refetch} />
        </div>

        {error && <div className="spa-alert spa-alert--error">{error}</div>}

        <ReportGenerator
          loading={loading}
          reports={reports}
          selectedReportId={selectedReportId}
          onSelect={setSelectedReportId}
          onGenerate={(id) => generateReport(id)}
        />

        <ComplianceChecklist loading={loading} checklist={checklist} />
      </div>
    </div>
  );
};

export default ComplianceReportsPage;
