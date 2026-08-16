/**
 * @typedef {Object} User
 * @property {string} id
 * @property {string} email
 * @property {string} name
 * @property {'organizer' | 'attendee'} role
 */

/**
 * @typedef {Object} AuthResponse
 * @property {string} token
 * @property {User} user
 */

/**
 * @typedef {Object} LoginRequest
 * @property {string} email
 * @property {string} password
 */

/**
 * @typedef {Object} RegisterRequest
 * @property {string} email
 * @property {string} password
 * @property {string} name
 */

/**
 * @typedef {Object} ResetPasswordRequest
 * @property {string} token
 * @property {string} newPassword
 */

export const authTypes = {
  User,
  AuthResponse,
  LoginRequest,
  RegisterRequest,
  ResetPasswordRequest,
};
