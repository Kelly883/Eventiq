// Re-exports the real fraud API client (fraudApi.ts). Kept as a separate
// file since other code in this feature already imports from
// './fraudService' rather than directly from fraudApi.
export { fraudApi as fraudService } from './fraudApi';
