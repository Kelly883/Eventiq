// Compliance feature types (structure-first scaffolding)

export type AuditLogType = 'user_action' | 'data_modification' | 'system_transaction';

export interface AuditLog {
  id: number;
  action: string;
  entity: string;
  entity_id?: number;
  changes?: Record<string, any>;
  user_id?: number;
  created_at?: string;
}

export interface ComplianceReport {
  id: string | number;
  name?: string;
  status?: string;
  generated_at?: string;
}

