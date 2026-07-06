import React from 'react';
import { usePaymentReconciliation } from '../hooks/usePaymentReconciliation';
import {
  PaymentTable,
  PaymentDetailsPanel,
  PaymentFilters,
  ReconciliationReport,
  PaymentMethodChart,
} from '../components/payments';

const AdminPaymentReconciliationPage = () => {
  const {
    loading,
    error,
    payments,
    selectedPayment,
    filters,
    setFilters,
    report,
    chart,
    setSelectedPayment,
  } = usePaymentReconciliation();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Payment Reconciliation</h1>
      </div>

      <PaymentFilters filters={filters} onFiltersChange={setFilters} />

      {error && <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>}

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div className="lg:col-span-2">
            <PaymentTable loading={loading} payments={payments} onRowClick={(p) => setSelectedPayment(p)} />
          </div>
          <div>
            <PaymentDetailsPanel loading={loading} payment={selectedPayment} />
          </div>
        </div>
        <div className="p-4 border-t">
          <ReconciliationReport loading={loading} report={report} />
          <div className="mt-4">
            <PaymentMethodChart loading={loading} chart={chart} />
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminPaymentReconciliationPage;

