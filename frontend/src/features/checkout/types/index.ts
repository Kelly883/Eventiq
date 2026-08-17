export type OrderStatus = 'pending' | 'completed' | 'failed' | 'refunded';

export interface Order {
  readonly id: string;
  readonly userId: string;
  readonly eventId: string;
  readonly totalAmount: number;
  readonly currency: string;
  readonly status: OrderStatus;
  readonly paymentGateway: string;
  readonly paymentIntentId: string;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface OrderItem {
  readonly id: string;
  readonly orderId: string;
  readonly ticketTierId: string;
  readonly quantity: number;
  readonly unitPrice: number;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export type TicketStatus = 'valid' | 'checked_in' | 'void' | 'fraud_flagged' | 'suspicious';

export interface Ticket {
  readonly id: string;
  readonly orderId: string;
  readonly userId: string;
  readonly eventId: string;
  readonly ticketTierId: string;
  readonly qrCodeData: string;
  readonly status: TicketStatus;
  readonly checkedInAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export type PaymentStatus = 'pending' | 'succeeded' | 'failed' | 'refunded' | 'requires_action';

export interface Payment {
  readonly id: string;
  readonly orderId: string;
  readonly paymentIntentId: string;
  readonly amount: number;
  readonly currency: string;
  readonly status: PaymentStatus;
  readonly gateway: string;
  readonly gatewayResponse: Record<string, unknown> | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}
