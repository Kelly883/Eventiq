// Runtime environment validation — DEGRADES GRACEFULLY.
//
// NOTE: a `throw` at import time here is fatal to the whole SPA — main.jsx
// imports this module first, so an exception produces a permanent white page
// for every visitor. Vite/Rollup does NOT execute modules during `vite build`,
// so a throw never actually failed the deployment pipeline the way the old
// "fail fast" comment intended; it only crashed the browser at runtime.
//
// The real, effective build-time gate now lives in `vite.config.js`
// (loadEnv + hard error when VITE_API_BASE_URL is missing for a production
// build). This runtime check is the LAST LINE OF DEFENSE: if a misconfigured
// bundle ever ships anyway, the public site still renders (data sections show
// error/empty states) and the misconfiguration is surfaced in the console and
// via `window.__eiMissingEnvVars__` for diagnostics — never a blank page.

const requiredInProd: Record<string, string> = {
  // Production API base URL — must be a fully-qualified HTTPS URL.
  VITE_API_BASE_URL: 'Production API base URL (e.g. https://app.eventiq.com)',
};

// eslint-disable-next-line @typescript-eslint/no-extraneous-class
export default class EnvValidator {
  static missing(): string[] {
    const meta = import.meta as any;
    if (!meta.env?.PROD) {
      return [];
    }

    const missing: string[] = [];
    for (const key of Object.keys(requiredInProd)) {
      const value: string | undefined = meta.env[key];
      if (!value) {
        missing.push(key);
      }
    }
    return missing;
  }

  static validate(): void {
    const missing = EnvValidator.missing();

    if (!missing.length) {
      return;
    }

    // Never throw — keep the app renderable. Surface the problem loudly.
    const message =
      `Missing required environment ${missing.length === 1 ? 'variable' : 'variables'}: ` +
      `${missing.join(', ')}. Without a valid API base URL, live data will not load. ` +
      `Rebuild with VITE_API_BASE_URL set (see vite.config.js).`;

    // eslint-disable-next-line no-console
    console.warn('[env] ' + message);

    if (typeof window !== 'undefined') {
      try {
        (window as any).__eiMissingEnvVars__ = missing;
      } catch {
        // ignore — diagnostics only
      }
    }
  }
}

// Auto-validate on import when running under Vite/build.
EnvValidator.validate();

// Simple named export so consuming modules can also check at runtime.
export { EnvValidator };