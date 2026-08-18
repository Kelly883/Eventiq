export type PushNotificationPermissionStatus = 'granted' | 'denied' | 'default';

export interface PushNotificationDevice {
  readonly id: string;
  readonly userId: string;
  readonly token: string;
  readonly provider: string;
  readonly deviceType: string;
  readonly deviceName: string | null;
  readonly model: string | null;
  readonly appVersion: string | null;
  readonly osVersion: string | null;
  readonly locale: string | null;
  readonly timezone: string | null;
  readonly lastError: string | null;
  readonly errorCount: number;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface PushNotificationTemplate {
  readonly id: string;
  readonly name: string;
  readonly type: string;
  readonly title: string;
  readonly body: string;
  readonly variables: string[];
  readonly isActive: boolean;
  readonly priority: number;
  readonly badge: number;
  readonly sound: string;
  readonly clickAction: string;
  readonly collapseKey: string;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface DeliveryPreferences {
  readonly pushNotificationsEnabled: boolean;
  readonly pushOrderConfirmation: boolean;
  readonly pushEventReminder: boolean;
  readonly pushCheckinAlert: boolean;
  readonly pushPromotionalOffers: boolean;
}

export interface PushNotificationHistory {
  readonly id: string;
  readonly userId: string;
  readonly deviceId: string;
  readonly templateId: string | null;
  readonly title: string;
  readonly body: string;
  readonly data: Record<string, unknown>;
  readonly status: string;
  readonly sentAt: string | null;
  readonly deliveredAt: string | null;
  readonly openedAt: string | null;
  readonly errorMessage: string | null;
  readonly gatewayResponse: Record<string, unknown> | null;
  readonly createdAt: string;
}
