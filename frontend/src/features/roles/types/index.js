// TypeScript interface definitions (can be converted to .d.ts later)

/**
 * @typedef {Object} Role
 * @property {number} id
 * @property {string} name
 * @property {string} [description]
 * @property {Permission[]} permissions
 * @property {string} createdAt
 * @property {string} updatedAt
 */

/**
 * @typedef {Object} Permission
 * @property {number} id
 * @property {string} name
 * @property {string} [description]
 * @property {string} group
 */

/**
 * @typedef {Object} AuditLog
 * @property {number} id
 * @property {string} action
 * @property {string} entity
 * @property {number} entityId
 * @property {Object} changes
 * @property {number} userId
 * @property {string} userEmail
 * @property {string} createdAt
 */

/**
 * @typedef {Object} PermissionRequest
 * @property {number} id
 * @property {number} userId
 * @property {string} userEmail
 * @property {number} permissionId
 * @property {string} reason
 * @property {'pending'|'approved'|'rejected'} status
 * @property {number} [approvedBy]
 * @property {string} createdAt
 */

/**
 * @typedef {Object} User
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {Role[]} roles
 * @property {Permission[]} permissions
 */
