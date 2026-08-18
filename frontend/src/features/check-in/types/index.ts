export type TicketStatus = 'valid' | 'checked_in' | 'void';

export interface Ticket {
  readonly id: string;
  readonly eventId: string;
  readonly orderId: string;
  readonly ticketTierId: string;
  readonly ticketId: string;
  readonly attendeeName: string;
  readonly attendeeEmail: string;
  readonly tier: string;
  readonly qrCodeData: string;
  readonly status: TicketStatus;
  readonly checkedInAt: string | null;
  readonly checkedInBy: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface FraudEvent {
  readonly id: string;
  readonly ticketId: string;
  readonly eventId: string;
  readonly fraudType: string;
  readonly detectedAt: string;
  readonly firstCheckInAt: string | null;
  readonly firstCheckInBy: string | null;
  readonly secondCheckInAt: string | null;
  readonly secondCheckInBy: string | null;
  readonly riskLevel: string;
  readonly notes: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface AuditLog {
  readonly id: string;
  readonly eventId: string;
  readonly userId: string;
  readonly action: string;
  readonly ticketId: string | null;
  readonly details: Record<string, unknown>;
  readonly createdAt: string;
}

export interface CheckInResult {
  readonly ticketId: string;
  readonly status: 'success' | 'error' | 'warning';
  readonly message: string;
  readonly previousCheckInAt?: string;
  readonly riskLevel: string;
}

export interface SearchResult {
  readonly results: Ticket[];
  readonly total: number;
  readonly query: string;
}

export interface StatsResponse {
  readonly totalCapacity: number;
  readonly totalCheckedIn: number;
  readonly totalRemaining: number;
  readonly totalVoid: number;
  readonly checkInRate: number;
  readonly lastUpdateAt: string;
}

export function formatCheckInTime(timestamp: string | null): string {
  if (!timestamp) {
    return 'Not checked in';
  }

  const date = new Date(timestamp);
  return date.toLocaleString();
}

export function getRiskLevelColor(riskLevel: string): string {
  switch (riskLevel) {
    case 'high':
      return 'text-red-600';
    case 'medium':
      return 'text-amber-600';
    case 'low':
      return 'text-green-600';
    default:
      return 'text-gray-600';
  }
}
