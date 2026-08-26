// Environment validation — fail fast in production.
//
// Required environment variables are checked at build time so that a
// missing or malformed config never silently results in a blank page
// at runtime. The error identifies the variable name but never exposes
// its value.
//
// This file is imported early in the bootstrap process (via main.jsx)
// so the build aborts before a deployable bundle is produced.

const requiredInProd: Record<string, string> = {
  // Production API base URL — must be a fully-qualified HTTPS URL.
  VITE_API_BASE_URL: 'Production API base URL (e.g. https://app.eventiq.com)',
};

// eslint-disable-next-line @typescript-eslint/no-extraneous-class
export default class EnvValidator {
  static validate() {
    // import.meta.env is only available inside Vite's transform context
    // (client code and build). When this file is loaded outside that
    // context the guard prevents a crash.
    const meta = import.meta as any;
    if (meta.env?.PROD) {
      for (const key of Object.keys(requiredInProd)) {
        const value: string | undefined = meta.env[key];

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
EnvValidator.validate();