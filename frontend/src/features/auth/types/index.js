// Type definitions for auth feature
// Can be converted to TypeScript .d.ts later if needed

/**
 * @typedef {Object} User
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {string} [avatar]
 */

/**
 * @typedef {Object} LoginCredentials
 * @property {string} email
 * @property {string} password
 */

/**
 * @typedef {Object} RegisterData
 * @property {string} name
 * @property {string} email
 * @property {string} password
 * @property {string} password_confirmation
 */

/**
 * @typedef {Object} PasswordStrength
 * @property {string} strength
 * @property {number} score
 * @property {string[]} requirements
 */
