export interface ApiKey {
  readonly id: string;
  readonly name: string;
  readonly keyPrefix: string;
  readonly scopes: string[];
  readonly lastUsedAt: string | null;
  readonly expiresAt: string | null;
  readonly revokedAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface Webhook {
  readonly id: string;
  readonly url: string;
  readonly subscribedEvents: string[];
  readonly status: 'active' | 'inactive' | 'failed';
  readonly lastFailureAt: string | null;
  readonly failureCount: number;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface ApiLog {
  readonly id: string;
  readonly action: string;
  readonly resourceType: string;
  readonly resourceId: string;
  readonly changes: Record<string, any>;
  readonly ipAddress: string;
  readonly createdAt: string;
}

export interface WebhookDeliveryLog {
  readonly id: string;
  readonly webhookId: string;
  readonly event: string;
  readonly payload: Record<string, any>;
  readonly status: 'success' | 'failed' | 'pending';
  readonly responseCode: number | null;
  readonly responseBody: string | null;
  readonly durationMs: number;
  readonly createdAt: string;
}
