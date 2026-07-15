import React from 'react';
import QRCodeDisplay from '../../tickets/components/QRCodeDisplay';
import DeliveryStatusBadge from '../../tickets/components/DeliveryStatusBadge';

const TicketStatusPage = () => {
  // TODO: fetch real ticket data via useQuery once the ticket-status
  // endpoint exists; qrCode/status are placeholders for now.
  const qrCode = null;
  const status = 'pending';

  return (
    <div className="p-6">
      <h1 className="text-xl font-bold mb-4">Ticket Status</h1>
      <DeliveryStatusBadge status={status} />
      <div className="mt-4">
        <QRCodeDisplay value={qrCode} />
      </div>
    </div>
  );
};

export default TicketStatusPage;
