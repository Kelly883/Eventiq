const _rawBase = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api').replace(/\/+$/, '');
const _normalizedBase = _rawBase.endsWith('/api') ? _rawBase : `${_rawBase}/api`;
const API_BASE_URL = `${_normalizedBase}/admin`;

export const roleService = {
  getRoles: async () => {
    // Implementation here
  },
  createRole: async (roleData) => {
    // Implementation here
  },
  updateRole: async (roleId, roleData) => {
    // Implementation here
  },
  deleteRole: async (roleId) => {
    // Implementation here
  },
  assignRoleToUser: async (roleId, userId) => {
    // Implementation here
  },
  removeRoleFromUser: async (roleId, userId) => {
    // Implementation here
  },
};

export const permissionService = {
  getPermissions: async () => {
    // Implementation here
  },
  updateRolePermissions: async (roleId, permissionIds) => {
    // Implementation here
  },
  getAuditLog: async () => {
    // Implementation here
  },
  getPermissionRequests: async () => {
    // Implementation here
  },
  approvePermissionRequest: async (requestId) => {
    // Implementation here
  },
  rejectPermissionRequest: async (requestId) => {
    // Implementation here
  },
  submitPermissionRequest: async (permissionId, reason) => {
    // Implementation here
  },
};
