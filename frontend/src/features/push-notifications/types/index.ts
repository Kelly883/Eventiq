export type PushNotificationPermissionStatus = 'granted' | 'denied' | 'default';

export interface PushNotificationDevice {
  readonly id: string;
  readonly userId: string;
  readonly token: string;
  readonly provider: string;
  readonly deviceType: string;
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
