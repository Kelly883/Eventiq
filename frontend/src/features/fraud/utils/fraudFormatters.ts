import type { DetectionMethod, EventType, FraudEventStatus, RiskLevel } from './fraud';
import { formatCurrency as formatCurrencyValue } from '@/lib/currencyUtils';

export function formatRiskScore(score: number): string {
  return score.toFixed(2);
}

export function getRiskLevelColor(level: RiskLevel): string {
  return level === 'high' ? 'text-red-600' : level === 'medium' ? 'text-amber-600' : 'text-green-600';
}

export function getFraudTypeBadgeColor(eventType: EventType): string {
  return eventType === 'card_testing' || eventType === 'high_risk_payment_method'
    ? 'text-red-600'
    : eventType === 'duplicate_ticket_attempt' || eventType === 'duplicate_checkin'
      ? 'text-amber-600'
      : 'text-gray-600';
}

export function getDetectionMethodBadgeColor(method: DetectionMethod): string {
  return method === 'sift_science' || method === 'stripe_radar'
    ? 'text-purple-600'
    : method === 'manual_review'
      ? 'text-blue-600'
      : method === 'rule_based'
        ? 'text-gray-600'
        : 'text-green-600';
}

export function getEventTypeLabel(type: EventType): string {
  const labels: Record<EventType, string> = {
    duplicate_ticket_attempt: 'Duplicate Ticket Attempt',
    velocity_check_failed: 'Velocity Check Failed',
    payment_pattern_suspicious: 'Suspicious Payment Pattern',
    device_fingerprint_mismatch: 'Device Fingerprint Mismatch',
    geolocation_anomaly: 'Geolocation Anomaly',
    card_testing: 'Card Testing',
    high_risk_payment_method: 'High Risk Payment Method',
    duplicate_checkin: 'Duplicate Check-in',
    invalid_qr: 'Invalid QR Code',
    manual_override: 'Manual Override',
  };

  return labels[type] ?? type.replace(/_/g, ' ');
}

export function getDetectionMethodLabel(method: DetectionMethod): string {
  const labels: Record<DetectionMethod, string> = {
    sift_science: 'Sift Science',
    stripe_radar: 'Stripe Radar',
    duplicate_detection: 'Duplicate Detection',
    velocity_check: 'Velocity Check',
    rule_based: 'Rule Based',
    qr_validation: 'QR Validation',
    manual_review: 'Manual Review',
  };

  return labels[method] ?? method.replace(/_/g, ' ');
}

export function formatCurrency(amount: number, currency: string): string {
  return formatCurrencyValue(amount, currency);
}

export function getStatusLabel(status: FraudEventStatus): string {
  const labels: Record<FraudEventStatus, string> = {
    flagged: 'Flagged',
    reviewed: 'Reviewed',
    approved: 'Approved',
    rejected: 'Rejected',
    auto_blocked: 'Auto Blocked',
  };

  return labels[status] ?? status;
}
