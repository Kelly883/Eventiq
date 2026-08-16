/**
 * @typedef {'event_management' | 'ticket_management' | 'analytics' | 'user_management' | 'platform_admin'} PermissionCategory
 * @typedef {'low' | 'medium' | 'high'} RiskLevel
 * @typedef {'pending' | 'approved' | 'denied'} PermissionRequestStatus
 */

/**
 * @typedef {Object} Role
 * @property {string} id
 * @property {string} name
 * @property {string} description
 * @property {string[]} permissions
 * @property {boolean} isSystemRole
 * @property {Date} createdAt
 * @property {Date} updatedAt
 */

/**
 * @typedef {Object} Permission
 * @property {string} id
 * @property {string} name
 * @property {string} description
 * @property {PermissionCategory} category
 * @property {RiskLevel} riskLevel
 * @property {Date} createdAt
 */

/**
 * @typedef {Object} AuditLog
 * @property {string} id
 * @property {import('../auth/types').User} admin
 * @property {import('../auth/types').User} targetUser
 * @property {string} action
 * @property {Object} oldValue - { [field: string]: { before: any, after: any } }
 * @property {Object} newValue - { [field: string]: { before: any, after: any } }
 * @property {string} [reason]
 * @property {Date} createdAt
 */

/**
 * @typedef {Object} PermissionRequest
 * @property {string} id
 * @property {string} userId
 * @property {string} permissionId
 * @property {PermissionRequestStatus} status
 * @property {string} [reason]
 * @property {import('../auth/types').User} [approvedBy]
 * @property {string} [approvalReason]
 * @property {Date} createdAt
 * @property {Date} [resolvedAt]
 */

export const rolesTypes = {
  Role,
  Permission,
  AuditLog,
  PermissionRequest,
};
