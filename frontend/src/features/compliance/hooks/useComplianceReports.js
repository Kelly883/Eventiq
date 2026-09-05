import { useEffect, useState } from 'react';
import { complianceService } from '../services/complianceService';

export const useComplianceReports = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [reports, setReports] = useState([]);
  const [selectedReportId, setSelectedReportId] = useState(null);
  const [checklist, setChecklist] = useState([]);

  const fetchReports = async () => {
    setLoading(true);
    setError(null);
    try {
      const [reportsData, checklistData] = await Promise.all([
        complianceService.getComplianceReports(),
        complianceService.getComplianceChecklist(),
      ]);

      setReports(reportsData);
      setChecklist(checklistData);
    } catch (e) {
      setError(e?.message ?? 'Failed to load reports');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReports();
  }, []);

  const generateReport = async (reportCode) => {
    if (!reportCode) return;
    setLoading(true);
    setError(null);
    try {
      const result = await complianceService.generateComplianceReport(reportCode, {});
      return result;
    } catch (e) {
      setError(e?.message ?? 'Failed to generate report');
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    reports,
    checklist,
    generateReport,
    selectedReportId,
    setSelectedReportId,
    refetch: fetchReports,
  };
};
