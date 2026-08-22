export interface ApiKey {
  readonly id: string;
  readonly organizerId: string;
  readonly name: string;
  readonly keyPrefix: string | null;
  readonly description: string | null;
  readonly scopes: string[];
  readonly lastUsedAt: string | null;
  readonly lastUsedIp: string | null;
  readonly expiresAt: string | null;
  readonly revokedAt: string | null;
  readonly rateLimit: number | null;
  readonly rateLimitPeriod: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface Webhook {
  readonly id: string;
  readonly organizerId: string;
  readonly url: string;
  readonly description: string | null;
  readonly subscribedEvents: string[];
  readonly status: 'active' | 'inactive' | 'failed';
  readonly lastFailureAt: string | null;
  readonly lastSuccessAt: string | null;
  readonly failureCount: number;
  readonly timeoutSeconds: number;
  readonly retryPolicy: Record<string, unknown> | null;
  readonly secret: string;
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

export interface ApiKeyListResponse {
  readonly data: ApiKey[];
  readonly total: number;
  readonly perPage: number;
  readonly currentPage: number;
  readonly lastPage: number;
}

export interface WebhookListResponse {
  readonly data: Webhook[];
  readonly total: number;
  readonly perPage: number;
  readonly currentPage: number;
  readonly lastPage: number;
}

export interface WebhookDeliveryLogListResponse {
  readonly data: WebhookDeliveryLog[];
  readonly total: number;
  readonly perPage: number;
  readonly currentPage: number;
  readonly lastPage: number;
}

export interface WebhookDeliveryLog {
  readonly id: string;
  readonly webhookId: string;
  readonly event: string;
  readonly attemptNumber: number;
  readonly payload: Record<string, any>;
  readonly status: 'success' | 'failed' | 'pending';
  readonly responseCode: number | null;
  readonly responseBody: string | null;
  readonly errorMessage: string | null;
  readonly durationMs: number;
  readonly createdAt: string;
}
