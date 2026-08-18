export type PaymentGatewayName = 'paystack' | 'flutterwave';

export interface Payment {
  readonly id: string;
  readonly gateway: PaymentGatewayName;
  readonly amount: number;
  readonly currency: string;
  readonly status: string;
  readonly paymentIntentId?: string;
  readonly gatewayTransactionId?: string;
  readonly paidAt?: string;
}

export interface RefundRequest {
  readonly id: string;
  readonly ticketId: string;
  readonly orderId: string;
  readonly userId: string;
  readonly eventId: string;
  readonly originalAmount: number;
  readonly refundAmount: number;
  readonly refundPercentage: number;
  readonly formattedPercentage: string;
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
  readonly paymentGateway?: PaymentGatewayName;
  readonly paymentIntentId?: string;
  readonly payment?: Payment;
  readonly appealCount: number;
  readonly lastAppealAt?: string;
  readonly formattedAmount: string;
  readonly formattedRefundAmount: string;
  readonly formattedPercentage: string;
  readonly remainingAppealAttempts: number;
  readonly statusBadgeColor: string;
  readonly isEligibleForAppeal: boolean;
  readonly canBeCancelled: boolean;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface RefundRequestListResponse {
  readonly data: RefundRequest[];
  readonly currentPage: number;
  readonly lastPage: number;
  readonly perPage: number;
  readonly total: number;
}

export interface RefundRequestDetailsResponse {
  readonly data: RefundRequest;
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
