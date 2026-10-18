import React from 'react';
import QRCodeDisplay from './QRCodeDisplay';

interface Ticket {
  readonly id: string;
  readonly eventId: string;
  readonly eventTitle: string;
  readonly eventDate: string;
  readonly eventVenue: string;
  readonly qrCodeData: string;
  readonly status: string;
}

interface TicketTier {
  readonly id: number;
  readonly name: string;
  readonly price: number;
  readonly currency: string;
}

interface TicketCardProps {
  ticket: Ticket;
  event?: { title: string; date: string; venue: string };
  ticketTier: TicketTier;
}

const TicketCard: TicketCardProps = ({ ticket, event, ticketTier }) => {
  const formattedDate = event?.date ? new Date(event.date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }) : 'Date not available';

  const formattedVenue = event?.venue ? event.venue : 'Venue not available';

  return (
    <div className="rounded-lg border border-slate-200 p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
      <div className="flex flex-col sm:flex-row gap-4 items-start">

        <div className="flex-1 min-w-0">
          <h3 className="text-lg font-bold text-slate-900 mb-1">
            {event?.title || ticket.eventTitle}
          </h3>
          <p className="text-sm text-slate-500 mb-2">
            {formattedDate}
          </p>
          <p className="text-sm text-slate-500">
            {formattedVenue}
          </p>
        </div>

        <div className="flex-shrink-0">
          <div className="ticket-tier-badge rounded bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
            {ticketTier.name}
          </div>
        </div>
      </div>

      <div className="mt-4 flex items-center justify-between">
        <QRCodeDisplay data={ticket.qrCodeData} size={150} />

        <div className="space-y-3">
          <button
            className="flex-1 bg-white border border-indigo-600 text-indigo-600 px-4 py-2 rounded-md font-medium text-sm hover:bg-indigo-600 text-white transition-colors"
            aria-label="Download ticket as PDF"
          >
            Download PDF
          </button>
          <button
            className="flex-1 bg-white border border-indigo-600 text-indigo-600 px-4 py-2 rounded-md font-medium text-sm hover:bg-indigo-600 text-white transition-colors"
            aria-label="Add to mobile wallet"
          >
            Add to Wallet
          </button>
        </div>
      </div>
    </div>
  );
};

export default TicketCard;