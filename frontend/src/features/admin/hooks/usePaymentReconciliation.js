import { useEffect, useState } from 'react';

export const usePaymentReconciliation = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [payments, setPayments] = useState([]);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [filters, setFilters] = useState({});
  const [report, setReport] = useState(null);
  const [chart, setChart] = useState(null);

  const fetchPayments = async () => {
    setLoading(true);
    setError(null);
    try {
      // Placeholder
      setPayments([]);
      setReport({});
      setChart({});
    } catch (e) {
      setError(e?.message ?? 'Failed to load payments');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPayments();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(filters)]);

  return {
    loading,
    error,
    payments,
    selectedPayment,
    filters,
    setFilters,
    report,
    chart,
    setSelectedPayment,
    refetch: fetchPayments,
  };
};

