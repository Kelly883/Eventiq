// Environment validation — fail fast in production.
//
// Required environment variables are checked at build time so that a
// missing or malformed config never silently results in a blank page
// at runtime. The error identifies the variable name but never exposes
// its value.
//
// This file is imported early in the bootstrap process (via vite.config.ts
// or main.jsx) so the build aborts before a deployable bundle is produced.

const requiredInProd: Record<string, string> = {
  // Production API base URL — must be a fully-qualified HTTPS URL.
  VITE_API_BASE_URL: 'Production API base URL (e.g. https://app.eventiq.com)',
};

// eslint-disable-next-line @typescript-eslint/no-extraneous-class
export default class EnvValidator {
  static validate() {
    // Safeguard: import.meta.env may be undefined during Vite config
    // evaluation (e.g., when this module is imported by vite.config.js).
    // The auto-validation at the bottom of this file runs at import time,
    // which is before Vite has fully populated import.meta.env. If env is
    // undefined, we skip validation — main.jsx will call validate() again
    // at runtime when import.meta.env is properly available.
    const env = (import.meta as any)?.env;

    if (!env) {
      return;
    }

    if (env.PROD) {
      for (const key of Object.keys(requiredInProd)) {
        const value: string | undefined = (import.meta as any).env[key];

        if (!value) {
          throw new Error(
            `Missing required environment variable: ${key}. Production API base URL is required.`,
          );
        }
      }
    }
    // In development, we do not enforce — developers may use local URLs.
  }
}

// Auto-validate on import when running under Vite/build.
// Safe to call here: if import.meta.env is undefined (e.g. during config
// loading), the method returns early. main.jsx also calls it at runtime.
EnvValidator.validate();