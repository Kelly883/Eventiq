import React from 'react';

const TrustSafetyPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">Trust & Safety</h1>
        <div className="space-y-6 text-slate-600">
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Verified Tickets</h2>
            <p>Every ticket on Eventiq is verified through our secure digital platform. QR codes are validated at entry, and counterfeit tickets are rejected automatically.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Secure Payments</h2>
            <p>We use industry-standard encryption and trusted payment processors. Your financial information is never stored on our servers.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Buyer Protection</h2>
            <p>If an event is cancelled, you'll receive a full refund automatically. For other issues, our support team is available to help resolve disputes quickly.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Organizer Verification</h2>
            <p>Event organizers undergo identity verification before they can list events. We monitor reviews and reports to maintain a safe marketplace.</p>
          </section>
        </div>
      </div>
    </div>
  );
};

export default TrustSafetyPage;
