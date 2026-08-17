export type TicketStatus = 'valid' | 'checked_in' | 'void';

export interface Ticket {
  readonly id: string;
  readonly eventId: string;
  readonly eventTitle: string;
  readonly eventDate: string;
  readonly eventVenue: string;
  readonly tierId: string;
  readonly tierName: string;
  readonly qrCodeData: string;
  readonly status: TicketStatus;
  readonly deliveryStatus: string;
  readonly deliveryMethod: string;
  readonly deliveryTimestamp: string | null;
  readonly orderId: string;
}

export interface TicketFilters {
  readonly status: TicketStatus | 'all';
  readonly dateRange: '7days' | '30days' | '90days' | 'all';
  readonly searchQuery: string;
}

export interface TicketDeliveryInfo {
  readonly status: string;
  readonly method: string;
  readonly timestamp: string | null;
  readonly errorMessage: string | null;
}
