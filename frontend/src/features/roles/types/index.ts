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
  admin: User;
  targetUser: User;
  action: string;
  oldValue: object;
  newValue: object;
  reason?: string;
  createdAt: Date;
}

export interface PermissionRequest {
  id: string;
  userId: string;
  permissionId: string;
  status: PermissionRequestStatus;
  reason?: string;
  approvedBy?: User;
  approvalReason?: string;
  createdAt: Date;
  resolvedAt?: Date;
}

export interface User {
  id: string;
  email: string;
  name: string;
  role: Role;
  permissions: string[];
  createdAt: Date;
}

import { z } from 'zod';

export const roleSchema = z.object({
  name: z.string().min(1).max(50),
  description: z.string().optional(),
  permissionIds: z.array(z.string().uuid()).optional(),
});

export const permissionRequestSchema = z.object({
  permissionId: z.string().uuid(),
  reason: z.string().max(500).optional(),
});

export const permissionApprovalSchema = z.object({
  approved: z.boolean(),
  approvalReason: z.string().max(500).optional(),
});
