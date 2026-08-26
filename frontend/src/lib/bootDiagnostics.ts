/**
 * Boot Diagnostics — internal diagnostics layer.
 *
 * Determines the health of the frontend build, API connection,
 * authentication state, and configured integrations (Firebase, payments).
 * Results are used by the Error Boundary and DevTools; values are
 * never exposed to users in production.
 */
export type DiagnosticStatus = 'OK' | 'FAIL' | 'MISSING' | 'CONFIGURED';

export interface BootReport {
  frontendBuild: DiagnosticStatus;
  apiUrlConfigured: DiagnosticStatus;
  apiReachable: DiagnosticStatus;
  csrfEndpoint: DiagnosticStatus;
  authentication: DiagnosticStatus;
  firebase: DiagnosticStatus;
  payments: DiagnosticStatus;
}

/**
 * Run a minimal set of diagnostics synchronously/near-synchronously.
 * This should be called early in the bootstrap process, after the
 * root is created but before or alongside the first render.
 *
 * The results are stored in window.__eiBootReport__ for consumption
 * by the Error Boundary, DevTools, or monitoring integration.
 * Secrets are never included.
 */
export function runBootDiagnostics(): BootReport {
  const report: BootReport = {
    frontendBuild: 'OK', // If this module loads, the build was successful
    apiUrlConfigured: 'MISSING',
    apiReachable: 'FAIL',
    csrfEndpoint: 'FAIL',
    authentication: 'FAIL',
    firebase: 'MISSING',
    payments: 'MISSING',
  };

  // 1. API Base URL configured?
  try {
    const baseURL =
      (import.meta as unknown as { env: { VITE_API_BASE_URL?: string } })
        .env?.VITE_API_BASE_URL;
    if (baseURL && baseURL.trim().length > 0 && !baseURL.startsWith('http://localhost')) {
      report.apiUrlConfigured = 'OK';
    } else if (baseURL && baseURL.startsWith('http://localhost')) {
      // Local dev URL — treat as OK for development only
      report.apiUrlConfigured = 'OK';
    } else {
      report.apiUrlConfigured = 'MISSING';
    }
  } catch {
    report.apiUrlConfigured = 'FAIL';
  }

  // 2. API reachability (light HEAD request with short timeout)
  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 5000);
    fetch(`${report.apiUrlConfigured === 'OK' ? report.apiUrlConfigured.split('/tickets')[0] : ''}/sanctum/csrf-cookie`, {
      method: 'GET',
      credentials: 'include',
      signal: controller.signal,
    })
      .then(() => {
        report.csrfEndpoint = 'OK';
      })
      .catch(() => {
        report.csrfEndpoint = 'FAIL';
      });
    clearTimeout(timeoutId);
    // Note: fetch is async; the report.csrfEndpoint may still be updating.
    // In production, consider running this after the first render cycle.
  } catch {
    // Synchronous failure — should not happen but be safe.
    report.csrfEndpoint = 'FAIL';
  }

  // 3. Authentication state check
  // If a global user object exists, treat as authenticated.
  try {
    const globalUser = (window as any).__eiUser;
    if (globalUser && globalUser?.id) {
      report.authentication = 'OK';
    }
  } catch {
    report.authentication = 'FAIL';
  }

  // 4. Firebase configuration check
  try {
    const firebaseConfig = (window as any).__fiConfig;
    if (firebaseConfig && firebaseConfig.apiKey) {
      report.firebase = 'CONFIGURED';
    }
  } catch {
    report.firebase = 'MISSING';
  }

  // 5. Payments configuration check
  try {
    const paymentsConfig = (window as any).__payConfig;
    if (paymentsConfig && (paymentsConfig.paystack || paymentsConfig.flutterwave)) {
      report.payments = 'CONFIGURED';
    }
  } catch {
    report.payments = 'MISSING';
  }

  // Store report globally for DevTools/Error Boundary consumption.
  // The Error Boundary reads window.__eiBootReport__ when rendering fallback.
  try {
    ;(window as any).__eiBootReport__ = report;
  } catch {
    // Ignore — global assignment failed; report is still returned.
  }

  return report;
}