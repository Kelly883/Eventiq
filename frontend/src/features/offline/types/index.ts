export interface DeviceToken {
  readonly token: string;
  readonly offlineEnabled: boolean;
  readonly lastSyncAt?: string;
}

export interface OfflineTicket {
  readonly id: string;
  readonly eventId: string;
  readonly eventName: string;
  readonly eventStartDate: string;
  readonly ticketTierId: string;
  readonly tierName: string;
  readonly qrCodeData: string;
  readonly orderId: string;
  readonly orderNumber: string;
}

export interface OfflineSyncState {
  readonly tickets: OfflineTicket[];
  readonly lastSyncedAt: string;
  readonly isSyncing: boolean;
  readonly syncError?: string;
}
