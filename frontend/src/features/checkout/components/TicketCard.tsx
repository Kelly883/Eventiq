import React, { useCallback, useState } from 'react';
import { formatCurrency } from '../../../lib/currencyUtils';
import QRCodeDisplay from './QRCodeDisplay';
import {
  addToWallet,
  downloadTicketPdf,
} from '../services/ticketDeliveryService';

interface Ticket {
  readonly id: string;
  readonly orderId?: string;
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
  /** Defer QR rendering until the card enters the viewport (multi-ticket perf). */
  lazyQr?: boolean;
}

const TicketCard: React.FC<TicketCardProps> = ({
  ticket,
  event,
  ticketTier,
  lazyQr = true,
}) => {
  const [pdfLoading, setPdfLoading] = useState(false);
  const [walletLoading, setWalletLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const dateStr = event?.date || ticket.eventDate;
  const formattedDate = dateStr
    ? new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    : 'Date not available';
  const formattedVenue = event?.venue || ticket.eventVenue || 'Venue not available';

  const handleDownloadPdf = useCallback(async () => {
    setError(null);
    setPdfLoading(true);
    try {
      await downloadTicketPdf({
        ticket,
        event,
        ticketTier,
        qrDataUrl: undefined,
      });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to generate ticket PDF.';
      setError(message);
    } finally {
      setPdfLoading(false);
    }
  }, [ticket, event, ticketTier]);

  const handleAddToWallet = useCallback(async () => {
    setError(null);
    setWalletLoading(true);
    try {
      await addToWallet({ ticketId: ticket.id });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to add to wallet.';
      setError(message);
    } finally {
      setWalletLoading(false);
    }
  }, [ticket.id]);

  const handleDismissError = useCallback(() => setError(null), []);

  return (
    <div className="rounded-lg border border-slate-200 p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
      {error && (
        <div
          className="mb-4 rounded bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700"
          role="alert"
          aria-live="polite"
        >
          {error}
          <button
            type="button"
            onClick={handleDismissError}
            className="ml-2 text-red-600 hover:underline"
            aria-label="Dismiss error"
          >
            ×
          </button>
        </div>
      )}

      <div className="flex flex-col sm:flex-row gap-4 items-start">
        <div className="flex-1 min-w-0">
          <h3 className="text-lg font-bold text-slate-900 mb-1">
            {event?.title || ticket.eventTitle}
          </h3>
          <p className="text-sm text-slate-500 mb-2">{formattedDate}</p>
          <p className="text-sm text-slate-500">{formattedVenue}</p>
          {ticketTier.price > 0 && (
            <p className="text-sm text-slate-500 mt-1">
              {formatCurrency(ticketTier.price, ticketTier.currency || 'USD')}
            </p>
          )}
        </div>

        <div className="flex-shrink-0">
          <div className="ticket-tier-badge rounded bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
            {ticketTier.name}
          </div>
        </div>
      </div>

      <div className="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <QRCodeDisplay
          data={ticket.qrCodeData}
          size={150}
          label={`Ticket ${ticket.id} QR code`}
          lazy={lazyQr}
        />

        <div className="space-y-3 w-full sm:w-auto sm:space-y-0 sm:space-x-3">
          <button
            type="button"
            onClick={handleDownloadPdf}
            disabled={pdfLoading}
            className={`flex-1 px-4 py-2 rounded-md font-medium text-sm transition-colors ${
              pdfLoading
                ? 'bg-slate-300 text-slate-500 cursor-not-allowed'
                : 'bg-white border border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white'
            }`}
            aria-busy={pdfLoading}
          >
            {pdfLoading ? 'Generating…' : 'Download PDF'}
          </button>
          <button
            type="button"
            onClick={handleAddToWallet}
            disabled={walletLoading}
            className={`flex-1 px-4 py-2 rounded-md font-medium text-sm transition-colors ${
              walletLoading
                ? 'bg-slate-300 text-slate-500 cursor-not-allowed'
                : 'bg-white border border-indigo-600 text-indigo-600 hover:bg-indigo-600 hover:text-white'
            }`}
            aria-busy={walletLoading}
          >
            {walletLoading ? 'Adding…' : 'Add to Wallet'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default TicketCard;