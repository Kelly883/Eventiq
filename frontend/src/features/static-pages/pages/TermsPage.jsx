import React from 'react';

const TermsPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">Terms of Service</h1>
        <div className="space-y-4 text-slate-600">
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Acceptance of Terms</h2>
            <p>By using Eventiq, you agree to these terms. If you do not agree, please do not use our platform.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">User Accounts</h2>
            <p>You are responsible for maintaining the confidentiality of your account and for all activities under your account.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Event Listings</h2>
            <p>Organizers must provide accurate event information. Eventiq reserves the right to remove listings that violate our policies.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Limitation of Liability</h2>
            <p>Eventiq is not liable for cancellations, changes, or issues arising from third-party events. Our liability is limited to the amount paid for tickets.</p>
          </section>
        </div>
      </div>
    </div>
  );
};

export default TermsPage;
