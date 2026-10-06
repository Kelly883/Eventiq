const _rawBase = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api').replace(/\/+$/, '');
const _normalizedBase = _rawBase.endsWith('/api') ? _rawBase : `${_rawBase}/api`;
const API_BASE_URL = `${_normalizedBase}/admin`;

export interface RoleData {
  name: string;
  description: string;
  permissions: string[];
}

export const roleService = {
  getRoles: async (): Promise<unknown> => {
    // Implementation here
  },
  createRole: async (roleData: RoleData): Promise<unknown> => {
    // Implementation here
  },
  updateRole: async (roleId: string, roleData: Partial<RoleData>): Promise<unknown> => {
    // Implementation here
  },
  deleteRole: async (roleId: string): Promise<unknown> => {
    // Implementation here
  },
  assignRoleToUser: async (roleId: string, userId: string): Promise<unknown> => {
    // Implementation here
  },
  removeRoleFromUser: async (roleId: string, userId: string): Promise<unknown> => {
    // Implementation here
  },
};

export interface PermissionData {
  roleId: string;
  permissionIds: string[];
}

export const permissionService = {
  getPermissions: async (): Promise<unknown> => {
    // Implementation here
  },
  updateRolePermissions: async (roleId: string, permissionIds: string[]): Promise<unknown> => {
    // Implementation here
  },
  getAuditLog: async (): Promise<unknown> => {
    // Implementation here
  },
  getPermissionRequests: async (): Promise<unknown> => {
    // Implementation here
  },
  approvePermissionRequest: async (requestId: string): Promise<unknown> => {
    // Implementation here
  },
  rejectPermissionRequest: async (requestId: string): Promise<unknown> => {
    // Implementation here
  },
  submitPermissionRequest: async (permissionId: string, reason?: string): Promise<unknown> => {
    // Implementation here
  },
};
