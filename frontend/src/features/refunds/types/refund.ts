export interface RefundRequest {
  readonly id: string;
  readonly ticketId: string;
  readonly orderId: string;
  readonly userId: string;
  readonly eventId: string;
  readonly originalAmount: number;
  readonly refundAmount: number;
  readonly refundPercentage: number;
  readonly reason: 'event_cancelled' | 'personal_circumstances' | 'duplicate_purchase' | 'other';
  readonly explanation?: string;
  readonly refundMethod: 'original_payment_method' | 'store_credit' | 'alternative_payment_method';
  readonly status: 'pending' | 'approved' | 'rejected' | 'processing' | 'completed' | 'failed';
  readonly rejectionReason?: string;
  readonly approvedBy?: string;
  readonly approvedAt?: string;
  readonly processingStartedAt?: string;
  readonly completedAt?: string;
  readonly paymentGatewayRefundId?: string;
  readonly appealCount: number;
  readonly lastAppealAt?: string;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface RefundPolicy {
  readonly id: string;
  readonly eventId: string;
  readonly organizerId: string;
  readonly refundWindowDays: number;
  readonly refundPercentageBeforeEvent: number;
  readonly refundPercentageAfterEventStart: number;
  readonly allowRefundsAfterEventStart: boolean;
  readonly processingTimeBusinessDays: number;
  readonly allowedRefundMethods: string[];
  readonly requiresApproval: boolean;
  readonly autoApproveThreshold?: number;
  readonly maxRefundsPerUser?: number;
  readonly refundReasons: string[];
  readonly cancellationPolicy?: string;
  readonly isActive: boolean;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface RefundAppeal {
  readonly id: string;
  readonly refundRequestId: string;
  readonly userId: string;
  readonly appealReason: string;
  readonly status: 'pending' | 'approved' | 'rejected';
  readonly reviewedBy?: string;
  readonly reviewNotes?: string;
  readonly reviewedAt?: string;
  readonly createdAt: string;
  readonly updatedAt: string;
}
