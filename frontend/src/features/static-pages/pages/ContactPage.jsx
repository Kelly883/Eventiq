import React from 'react';

const ContactPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">Contact Us</h1>
        <div className="space-y-4 text-slate-600">
          <p>We'd love to hear from you. Reach out to our support team for help with events, tickets, or any other questions.</p>
          <div className="mt-6 space-y-2">
            <p><strong>Email:</strong> support@eventiq.com</p>
            <p><strong>Phone:</strong> +234 1 234 5678</p>
            <p><strong>Address:</strong> 123 Event Street, Lagos, Nigeria</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ContactPage;
