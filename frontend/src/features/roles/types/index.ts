export type PermissionCategory =
  | 'event_management'
  | 'ticket_management'
  | 'analytics'
  | 'user_management'
  | 'platform_admin';

export type RiskLevel = 'low' | 'medium' | 'high';

export type PermissionRequestStatus = 'pending' | 'approved' | 'denied';

export interface Role {
  id: string;
  name: string;
  description: string;
  permissions: string[];
  isSystemRole: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface Permission {
  id: string;
  name: string;
  description: string;
  category: PermissionCategory;
  riskLevel: RiskLevel;
  createdAt: Date;
}

export interface AuditLog {
  id: string;
  admin: {
    id: string;
    name: string;
    email: string;
  };
  targetUser: {
    id: string;
    name: string;
    email: string;
  };
  action: string;
  oldValue: Record<string, { before: unknown; after: unknown }>;
  newValue: Record<string, { before: unknown; after: unknown }>;
  reason?: string;
  createdAt: Date;
}

export interface PermissionRequest {
  id: string;
  userId: string;
  permissionId: string;
  status: PermissionRequestStatus;
  reason?: string;
  approvedBy?: {
    id: string;
    name: string;
    email: string;
  };
  approvalReason?: string;
  createdAt: Date;
  resolvedAt?: Date;
}

export interface User {
  id: string;
  email: string;
  name: string;
  role: string;
  permission_count?: number;
  created_at?: string;
  permissions?: Array<{ id?: string; name: string }>;
  updated_at?: string;
}

export interface AuditLogEntry {
  id: string;
  createdAt: string;
  adminName?: string;
  user?: { name?: string };
  targetUser?: { name?: string };
  target_id?: string;
  action: string;
  oldValue?: unknown;
  newValue?: unknown;
  reason?: string;
}

export interface PermissionItem {
  name: string;
  description?: string;
  enabled?: boolean;
}

export type SortDirection = 'asc' | 'desc';

export interface SortState {
  key: string;
  direction: SortDirection;
}

export interface OptimisticUpdate {
  userId: string;
}
