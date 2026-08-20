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
  readonly ticketTierId: string;
  readonly tierName: string | null;
  readonly qrCodeData: string;
  readonly orderId: string;
  readonly orderNumber: string | null;
}

export interface OfflineSyncState {
  readonly tickets: OfflineTicket[];
  readonly lastSyncedAt: string;
  readonly isSyncing: boolean;
  readonly syncError?: string;
}
