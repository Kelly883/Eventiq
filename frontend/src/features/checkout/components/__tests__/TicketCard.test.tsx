import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import { vi } from 'vitest';
import TicketCard from '../TicketCard';
import QRCodeDisplay from '../QRCodeDisplay';
import { downloadTicketPdf, addToWallet } from '../../services/ticketDeliveryService';

expect.extend(toHaveNoViolations);

const mockTicket = {
  id: 'tick_123',
  orderId: 'order_123',
  eventId: 'evt_1',
  eventTitle: 'Jazz Night Live',
  eventDate: '2026-12-15T19:00:00Z',
  eventVenue: 'The Blue Note, New York',
  qrCodeData: 'TICKET|tick_123|valid|2026-12-15',
  status: 'valid',
};

const mockEvent = {
  title: 'Jazz Night Live',
  date: '2026-12-15T19:00:00Z',
  venue: 'The Blue Note, New York',
};

const mockTicketTier = {
  id: 1,
  name: 'VIP',
  price: 15000,
  currency: 'USD',
};

vi.mock('../../services/ticketDeliveryService', () => ({
  downloadTicketPdf: vi.fn().mockResolvedValue(undefined),
  addToWallet: vi.fn().mockResolvedValue('https://example.com/pass.pkpass'),
}));

describe('TicketCard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    // Mock window.open for PDF download
    global.open = vi.fn(() => ({
      document: {
        write: vi.fn(),
        close: vi.fn(),
      },
      print: vi.fn(),
      close: vi.fn(),
    })) as any;
  });

  it('renders ticket information correctly', () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    expect(screen.getByText('Jazz Night Live')).toBeInTheDocument();
    expect(screen.getByText('December 15, 2026')).toBeInTheDocument();
    expect(screen.getByText('The Blue Note, New York')).toBeInTheDocument();
    expect(screen.getByText('VIP')).toBeInTheDocument();
    expect(screen.getByText('$15,000.00')).toBeInTheDocument();
  });

  it('renders QR code', () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const qrCode = screen.getByRole('img', { name: /Ticket tick_123 QR code/i });
    expect(qrCode).toBeInTheDocument();
  });

  it('has Download PDF button', () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const downloadBtn = screen.getByRole('button', { name: /Download PDF/i });
    expect(downloadBtn).toBeInTheDocument();
    expect(downloadBtn).not.toBeDisabled();
  });

  it('has Add to Wallet button', () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const walletBtn = screen.getByRole('button', { name: /Add to Wallet/i });
    expect(walletBtn).toBeInTheDocument();
    expect(walletBtn).not.toBeDisabled();
  });

  it('calls downloadTicketPdf when Download PDF button is clicked', async () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const downloadBtn = screen.getByRole('button', { name: /Download PDF/i });
    fireEvent.click(downloadBtn);
    
    await waitFor(() => {
      expect(downloadTicketPdf).toHaveBeenCalledWith(
        expect.objectContaining({
          ticket: mockTicket,
          event: mockEvent,
          ticketTier: mockTicketTier,
        })
      );
    });
  });

  it('calls addToWallet when Add to Wallet button is clicked', async () => {
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const walletBtn = screen.getByRole('button', { name: /Add to Wallet/i });
    fireEvent.click(walletBtn);
    
    await waitFor(() => {
      expect(addToWallet).toHaveBeenCalledWith({ ticketId: 'tick_123' });
    });
  });

  it('shows loading state during PDF generation', async () => {
    downloadTicketPdf.mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));
    
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const downloadBtn = screen.getByRole('button', { name: /Download PDF/i });
    fireEvent.click(downloadBtn);
    
    expect(screen.getByRole('button', { name: /Generating…/i })).toBeInTheDocument();
    expect(downloadBtn).toBeDisabled();
    
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Download PDF/i })).toBeInTheDocument();
    });
  });

  it('shows error message when PDF generation fails', async () => {
    downloadTicketPdf.mockRejectedValue(new Error('Failed to generate PDF'));
    
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const downloadBtn = screen.getByRole('button', { name: /Download PDF/i });
    fireEvent.click(downloadBtn);
    
    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/Failed to generate PDF/i);
    });
  });

  it('shows error message when wallet provisioning fails', async () => {
    addToWallet.mockRejectedValue(new Error('Wallet provisioning failed'));
    
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const walletBtn = screen.getByRole('button', { name: /Add to Wallet/i });
    fireEvent.click(walletBtn);
    
    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/Wallet provisioning failed/i);
    });
  });

  it('can dismiss error message', async () => {
    const { downloadTicketPdf } = await import('../../services/ticketDeliveryService');
    downloadTicketPdf.mockRejectedValue(new Error('Failed to generate PDF'));
    
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    
    const downloadBtn = screen.getByRole('button', { name: /Download PDF/i });
    fireEvent.click(downloadBtn);
    
    await waitFor(() => {
      expect(screen.getByRole('alert')).toBeInTheDocument();
    });
    
    const dismissBtn = screen.getByRole('button', { name: /Dismiss error/i });
    fireEvent.click(dismissBtn);
    
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  });

  it('passes accessibility audit', async () => {
    const { container } = render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={mockTicketTier} />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('renders without event prop (uses ticket data)', () => {
    render(<TicketCard ticket={mockTicket} ticketTier={mockTicketTier} />);
    
    expect(screen.getByText('Jazz Night Live')).toBeInTheDocument();
    expect(screen.getByText('December 15, 2026')).toBeInTheDocument();
    expect(screen.getByText('The Blue Note, New York')).toBeInTheDocument();
  });

  it('does not show price for free tickets', () => {
    const freeTicketTier = { ...mockTicketTier, price: 0 };
    render(<TicketCard ticket={mockTicket} event={mockEvent} ticketTier={freeTicketTier} />);
    
    expect(screen.queryByText('$0.00')).not.toBeInTheDocument();
    expect(screen.queryByText('$15,000.00')).not.toBeInTheDocument();
  });
});

describe('QRCodeDisplay', () => {
  it('renders QR code from data string', () => {
    render(<QRCodeDisplay data="https://example.com/ticket/123" size={150} label="Test QR" />);
    
    const svg = screen.getByRole('img', { name: /Test QR/i });
    expect(svg).toBeInTheDocument();
    expect(svg).toHaveAttribute('width', '150');
    expect(svg).toHaveAttribute('height', '150');
  });

  it('shows fallback when data is empty', () => {
    render(<QRCodeDisplay data="" label="Empty QR" />);
    
    expect(screen.getByText('QR code not available')).toBeInTheDocument();
  });

  it('shows loading placeholder when lazy and not yet rendered', () => {
    render(<QRCodeDisplay data="https://example.com/ticket/123" size={150} label="Test QR" lazy />);
    
    expect(screen.getByText('QR code')).toBeInTheDocument();
  });

  it('passes accessibility audit', async () => {
    const { container } = render(<QRCodeDisplay data="https://example.com/ticket/123" size={150} label="Test QR" />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});