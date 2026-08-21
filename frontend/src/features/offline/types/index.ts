export interface DeviceToken {
  readonly token: string;
  readonly offlineEnabled: boolean;
  readonly lastSyncAt?: string;
}

export interface OfflineTicket {
  readonly id: string;
  readonly eventId: string;
  readonly eventName: string | null;
  readonly eventStartDate: string | null;
  readonly eventEndDate: string | null;
  readonly eventUpdatedAt: string | null;
  readonly venueName: string | null;
  readonly venueAddress: string | null;
  readonly ticketTierId: string;
  readonly tierName: string | null;
  readonly tierUpdatedAt: string | null;
  readonly qrCodeData: string | null;
  readonly orderId: string;
  readonly orderNumber: string | null;
  readonly orderDate: string | null;
  readonly attendeeName: string | null;
  readonly attendeeEmail: string | null;
  readonly status: string;
  readonly refundStatus: string | null;
  readonly checkedInAt: string | null;
  readonly checkedInBy: string | null;
  readonly qrCodeExpiresAt: string | null;
  readonly qrCodeScannedCount: number | null;
  readonly lastQrScanAt: string | null;
  readonly ticketType: string | null;
  readonly pricePaid: string | null;
  readonly paymentStatus: string | null;
  readonly barcodeType: 'qr' | 'barcode';
}

export interface OfflineSyncState {
  readonly tickets: OfflineTicket[];
  readonly lastSyncedAt: string;
  readonly isSyncing: boolean;
  readonly syncError?: string;
  readonly syncVersion?: number;
  readonly serverTime?: string;
  readonly deletedTicketIds?: string[];
}
