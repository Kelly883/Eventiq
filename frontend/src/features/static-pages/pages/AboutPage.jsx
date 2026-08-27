import React from 'react';

const AboutPage = () => {
  return (
    <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
      <div className="max-w-3xl w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
        <h1 className="text-3xl font-bold text-slate-900 mb-6">About Eventiq</h1>
        <div className="space-y-4 text-slate-600">
          <p>Eventiq is a modern event ticketing platform that connects event-goers with unforgettable experiences. From concerts and festivals to conferences and comedy shows, we make discovering and attending events seamless.</p>
          <p>Our mission is to fill venues and create memorable moments by making event discovery simple, ticket purchasing secure, and entry effortless.</p>
        </div>
      </div>
    </div>
  );
};

export default AboutPage;
