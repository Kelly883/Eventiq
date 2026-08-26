import { fraudApi } from './fraudApi';

const CACHE_TTL_MS = 60_000;
const velocityCache = new Map();
const ipCache = new Map();
const deviceCache = new Map();
const statsCache = { value: null, expiresAt: 0 };

function getCached(map, key) {
  const entry = map.get(key);
  if (!entry) return null;
  if (Date.now() > entry.expiresAt) {
    map.delete(key);
    return null;
  }
  return entry.value;
}

function setCached(map, key, value, ttl = CACHE_TTL_MS) {
  map.set(key, { value, expiresAt: Date.now() + ttl });
}

function camelToSnake(obj) {
  if (obj == null || typeof obj !== 'object') return obj;
  if (Array.isArray(obj)) return obj.map(camelToSnake);
  return Object.fromEntries(
    Object.entries(obj).map(([k, v]) => [
      k.replace(/[A-Z]/g, (ch) => `_${ch.toLowerCase()}`),
      camelToSnake(v),
    ])
  );
}

function normalizeDecision(decision) {
  if (!decision) return decision;
  return {
    ...decision,
    risk_score: Number(decision.risk_score ?? 0),
    riskLevel: decision.risk_score >= 80 ? 'critical'
      : decision.risk_score >= 60 ? 'high'
      : decision.risk_score >= 30 ? 'medium'
      : 'low',
    isBlocked: decision.decision === 'block',
    requiresReview: decision.decision === 'review',
    isApproved: decision.decision === 'approve',
    triggeredAt: new Date().toISOString(),
  };
}

function normalizeAlert(alert) {
  if (!alert) return alert;
  return {
    ...alert,
    riskScore: Number(alert.risk_score ?? alert.riskScore ?? 0),
    createdAt: alert.created_at || alert.createdAt,
    updatedAt: alert.updated_at || alert.updatedAt,
    userId: alert.user_id ?? alert.userId,
    orderId: alert.order_id ?? alert.orderId,
    eventType: alert.event_type ?? alert.eventType,
    riskLevel: alert.risk_level ?? alert.riskLevel,
  };
}

function getClientFingerprint() {
  try {
    const nav = typeof navigator !== 'undefined' ? navigator : null;
    const screen = typeof window !== 'undefined' ? window.screen : null;
    return {
      user_agent: nav?.userAgent ?? null,
      language: nav?.language ?? null,
      platform: nav?.platform ?? null,
      screen_resolution: screen ? `${screen.width}x${screen.height}` : null,
      timezone: Intl?.DateTimeFormat?.()?.resolvedOptions?.()?.timeZone ?? null,
    };
  } catch {
    return {};
  }
}

export const fraudService = {
  async performRiskCheck(transaction) {
    const payload = camelToSnake({
      ...transaction,
      ...getClientFingerprint(),
      checked_at: new Date().toISOString(),
    });

    const raw = await fraudApi.detectFraudRisk(payload);
    return normalizeDecision(raw);
  },

  async verifyPayment(provider, reference) {
    if (!reference) {
      throw new Error('Payment reference is required for verification');
    }
    if (provider === 'paystack') {
      return fraudApi.verifyPaystackTransaction(reference);
    }
    if (provider === 'flutterwave') {
      return fraudApi.verifyFlutterwaveTransaction(reference);
    }
    throw new Error(`Unsupported payment provider: ${provider}`);
  },

  async getUserVelocity(userId, { amount = 0, useCache = true } = {}) {
    const cacheKey = `${userId}:${amount}`;
    if (useCache) {
      const cached = getCached(velocityCache, cacheKey);
      if (cached) return cached;
    }
    const result = await fraudApi.checkVelocity(userId, amount);
    setCached(velocityCache, cacheKey, result, 30_000);
    return result;
  },

  async isDuplicateTicket(ticketTierId, qrCode) {
    if (!ticketTierId || !qrCode) {
      return { duplicate: false, reason: 'missing_input' };
    }
    const result = await fraudApi.detectDuplicateTickets(ticketTierId, qrCode);
    return {
      duplicate: Boolean(result?.duplicate || result?.is_duplicate),
      matches: result?.matches ?? 0,
      firstSeenAt: result?.first_seen_at ?? null,
    };
  },

  async checkIpReputation(ip, { useCache = true } = {}) {
    if (!ip) {
      return { ip: null, risk: 'unknown', score: 0 };
    }
    if (useCache) {
      const cached = getCached(ipCache, ip);
      if (cached) return cached;
    }
    const raw = await fraudApi.checkIpReputation(ip);
    const enriched = {
      ip,
      risk: raw?.risk ?? raw?.risk_level ?? 'unknown',
      score: Number(raw?.score ?? raw?.risk_score ?? 0),
      isBlacklisted: Boolean(raw?.blacklisted ?? raw?.is_blacklisted ?? false),
      country: raw?.country ?? raw?.country_code ?? null,
      asn: raw?.asn ?? null,
    };
    setCached(ipCache, ip, enriched);
    return enriched;
  },

  async checkDevice(deviceId, { useCache = true } = {}) {
    if (!deviceId) {
      return { device_id: null, risk: 'unknown', transactionCount: 0 };
    }
    if (useCache) {
      const cached = getCached(deviceCache, deviceId);
      if (cached) return cached;
    }
    const raw = await fraudApi.checkDeviceFingerprint(deviceId);
    const enriched = {
      device_id: deviceId,
      risk: raw?.risk ?? raw?.risk_level ?? 'low',
      transactionCount: Number(raw?.count ?? raw?.transaction_count ?? 0),
      limit: Number(raw?.limit ?? raw?.rate_limit ?? 10),
      exceeded: Boolean(raw?.exceeded ?? raw?.limit_exceeded ?? false),
      firstSeenAt: raw?.first_seen_at ?? null,
    };
    setCached(deviceCache, deviceId, enriched);
    return enriched;
  },

  async getTransactionHistory(reference, provider) {
    if (!reference) return null;
    return fraudApi.getTransactionDetails(reference, provider);
  },

  async listAlerts(filters = {}) {
    const params = camelToSnake(filters);
    const rawList = await fraudApi.listAlerts(params);
    return Array.isArray(rawList) ? rawList.map(normalizeAlert) : [];
  },

  async getAlert(alertId) {
    const raw = await fraudApi.getAlert(alertId);
    return normalizeAlert(raw);
  },

  async resolveAlert(alertId, { decision, notes, status } = {}) {
    if (!decision && !status) {
      throw new Error('Either decision or status must be provided to resolve an alert');
    }
    const payload = {
      status: status ?? (decision === 'approve' ? 'resolved'
        : decision === 'reject' ? 'resolved'
        : decision === 'escalate' ? 'escalated'
        : decision === 'dismiss' ? 'dismissed'
        : 'reviewed'),
      decision,
      notes,
      resolved_at: new Date().toISOString(),
    };
    const raw = await fraudApi.updateAlertStatus(alertId, payload);
    return normalizeAlert(raw);
  },

  async getDashboardStats({ useCache = true } = {}) {
    if (useCache && Date.now() < statsCache.expiresAt) {
      return statsCache.value;
    }
    const raw = await fraudApi.getDashboardStats();
    const computed = {
      ...raw,
      totalAlertsToday: raw?.total_alerts_today ?? 0,
      pendingReview: raw?.pending_review ?? 0,
      criticalAlerts: raw?.critical_alerts ?? 0,
      resolvedToday: raw?.resolved_today ?? 0,
      avgRiskScore: Number(raw?.avg_risk_score ?? 0),
      fraudPreventionRate: Number(raw?.fraud_prevention_rate ?? 0),
      flaggedRevenue: Number(raw?.flagged_revenue ?? 0),
      resolutionRate: raw?.total_alerts_today
        ? Math.round((raw.resolved_today / raw.total_alerts_today) * 100) / 100
        : 0,
    };
    statsCache.value = computed;
    statsCache.expiresAt = Date.now() + 30_000;
    return computed;
  },

  async runDiagnosticCheck(checkId, payload = {}) {
    if (!checkId) throw new Error('Check ID is required');
    return fraudApi.runManualCheck(checkId, camelToSnake(payload));
  },

  shouldEscalate(decision) {
    if (!decision) return false;
    if (decision.isBlocked) return true;
    if (decision.requiresReview && decision.risk_score >= 70) return true;
    if (decision.flags?.includes('stolen_card') || decision.flags?.includes('chargeback_risk')) return true;
    return false;
  },

  clearCaches() {
    velocityCache.clear();
    ipCache.clear();
    deviceCache.clear();
    statsCache.value = null;
    statsCache.expiresAt = 0;
  },
};
