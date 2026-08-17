import type { DetectionMethod, EventType, FraudEventStatus, RiskLevel } from './fraud';

export function formatRiskScore(score: number): string {
  return score.toFixed(2);
}

export function getRiskLevelColor(level: RiskLevel): string {
  return level === 'high' ? 'text-red-600' : level === 'medium' ? 'text-amber-600' : 'text-green-600';
}

export function getEventTypeLabel(type: EventType): string {
  const labels: Record<EventType, string> = {
    suspicious_login: 'Suspicious Login',
    multiple_failed_payments: 'Multiple Failed Payments',
    unusual_location: 'Unusual Location',
    ticket_scalping: 'Ticket Scalping',
    duplicate_order: 'Duplicate Order',
    high_velocity_purchases: 'High Velocity Purchases',
    blacklisted_ip: 'Blacklisted IP',
    stolen_card: 'Stolen Card',
    chargeback_risk: 'Chargeback Risk',
    identity_mismatch: 'Identity Mismatch',
  };

  return labels[type] ?? type.replace(/_/g, ' ');
}

export function getDetectionMethodLabel(method: DetectionMethod): string {
  const labels: Record<DetectionMethod, string> = {
    rule_based: 'Rule Based',
    ml_model: 'ML Model',
    manual_review: 'Manual Review',
    velocity_check: 'Velocity Check',
    device_fingerprint: 'Device Fingerprint',
    geolocation: 'Geolocation',
  };

  return labels[method] ?? method.replace(/_/g, ' ');
}

export function formatCurrency(amount: number, currency: string): string {
  const symbols: Record<string, string> = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    NGN: '₦',
    GHS: '₵',
    KES: 'KSh',
    ZAR: 'R',
  };

  const symbol = symbols[currency.toUpperCase()] ?? currency.toUpperCase();
  return `${symbol}${amount.toFixed(2)}`;
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
