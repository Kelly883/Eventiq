import { useEffect, useState } from 'react';
import { complianceService } from '../services/complianceService';
import { normalizeAuditLog } from '../types/audit';

export const useAuditLogs = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [logs, setLogs] = useState([]);
  const [filters, setFilters] = useState({ query: '', start: '', end: '', action: '', targetType: '', status: '' });
  const [selectedIds, setSelectedIds] = useState([]);

  const fetchLogs = async () => {
    setLoading(true);
    setError(null);
    try {
      const params = {
        per_page: 20,
        ...(filters.action && { action: filters.action }),
        ...(filters.targetType && { target_type: filters.targetType }),
        ...(filters.status && { status: filters.status }),
        ...(filters.start && { from: filters.start }),
        ...(filters.end && { to: filters.end }),
        ...(filters.query && { search: filters.query }),
      };

      const response = await complianceService.getAuditLogs(params);
      const rawLogs = response?.data ?? [];
      setLogs(rawLogs.map((raw) => normalizeAuditLog(raw)));
      setSelectedIds([]);
    } catch (e) {
      setError(e?.message ?? 'Failed to load audit logs');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [JSON.stringify(filters)]);

  const bulkAction = async (action) => {
    if (!selectedIds.length) return;
    setLoading(true);
    setError(null);
    try {
      if (action === 'tag') {
        const tag = prompt('Enter tag name:');
        if (tag) {
          await complianceService.bulkTagAuditLogs(selectedIds, tag);
          await fetchLogs();
        }
      } else if (action === 'export') {
        await complianceService.exportAuditLogs({
          per_page: 1000,
          ...(filters.action && { action: filters.action }),
          ...(filters.targetType && { target_type: filters.targetType }),
          ...(filters.status && { status: filters.status }),
          ...(filters.start && { from: filters.start }),
          ...(filters.end && { to: filters.end }),
          ...(filters.query && { search: filters.query }),
        });
      }
    } catch (e) {
      setError(e?.message ?? 'Bulk action failed');
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    logs,
    filters,
    setFilters,
    selectedIds,
    setSelectedIds,
    bulkAction,
    refetch: fetchLogs,
  };
};
