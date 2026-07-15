import React from 'react';
import { QRCodeSVG } from 'qrcode.react';

/**
 * Renders a ticket's QR code for TicketStatusPage.
 * `value` should be the ticket's unique validation payload (e.g. a signed
 * token or the ticket's QR code string from the backend), not raw ticket ID.
 */
const QRCodeDisplay = ({ value, size = 200 }) => {
  if (!value) {
    return <div className="text-sm text-gray-500">QR code not yet available</div>;
  }

  return (
    <div className="inline-block p-4 bg-white rounded-lg shadow-sm">
      <QRCodeSVG value={value} size={size} level="M" includeMargin />
    </div>
  );
};

export default QRCodeDisplay;
