import React from 'react';

const RefundPolicyPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">Refund Policy</h1>
        <div className="space-y-4 text-slate-600">
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Event Cancellations</h2>
            <p>If an event is cancelled by the organizer, all ticket holders will receive a full refund automatically to their original payment method within 5-10 business days.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">No-Shows & Changes</h2>
            <p>Refunds are not available for no-shows or changes in personal circumstances. Please check event details carefully before purchasing.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Disputed Charges</h2>
            <p>If you believe a charge is incorrect, contact our support team within 30 days of the transaction for assistance.</p>
          </section>
        </div>
      </div>
    </div>
  );
};

export default RefundPolicyPage;
