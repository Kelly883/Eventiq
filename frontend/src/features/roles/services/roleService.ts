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
    return undefined;
  },
  createRole: async (roleData: RoleData): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  updateRole: async (roleId: string, roleData: Partial<RoleData>): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  deleteRole: async (roleId: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  assignRoleToUser: async (roleId: string, userId: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  removeRoleFromUser: async (roleId: string, userId: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
};

export interface PermissionData {
  roleId: string;
  permissionIds: string[];
}

export const permissionService = {
  getPermissions: async (): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  updateRolePermissions: async (roleId: string, permissionIds: string[]): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  getAuditLog: async (): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  getPermissionRequests: async (): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  approvePermissionRequest: async (requestId: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  rejectPermissionRequest: async (requestId: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
  submitPermissionRequest: async (permissionId: string, reason?: string): Promise<unknown> => {
    // Implementation here
    return undefined;
  },
};
