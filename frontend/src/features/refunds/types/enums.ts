export type RefundStatus = 'pending' | 'approved' | 'rejected' | 'processing' | 'completed' | 'failed';

export type RefundReason = 'event_cancelled' | 'personal_circumstances' | 'duplicate_purchase' | 'other';

export type RefundMethod = 'original_payment_method' | 'store_credit' | 'alternative_payment_method';

export type AppealStatus = 'pending' | 'approved' | 'rejected';
