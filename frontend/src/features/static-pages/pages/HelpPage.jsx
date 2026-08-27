import React from 'react';

const HelpPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">Help Center</h1>
        <div className="space-y-6 text-slate-600">
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Buying Tickets</h2>
            <p>Learn how to search for events, select seats, and complete your purchase securely.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Managing Tickets</h2>
            <p>Find your tickets in the "My Tickets" section, download them, or transfer them to friends.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Event Organizers</h2>
            <p>Guidelines for creating events, setting up ticket tiers, and managing check-in.</p>
          </section>
          <section>
            <h2 className="text-xl font-semibold text-slate-900 mb-2">Refunds & Cancellations</h2>
            <p>Our refund policy and how to request a refund if your event is cancelled.</p>
          </section>
        </div>
      </div>
    </div>
  );
};

export default HelpPage;
