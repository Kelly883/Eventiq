// Admin feature types (structure-first scaffolding)

export interface AdminSettings {
  payoutProcessingEnabled?: boolean;
}

export interface AuditLog {
  id: number;
  action: string;
  entity: string;
  entity_id?: number;
  changes?: Record<string, any>;
  user_id?: number;
  created_at?: string;
}

export interface Admin {
  id: number;
  name?: string;
  role?: string;
}

export interface User {
  id: number;
  name?: string;
  role?: string;
}

export interface Event {
  id: number;
  title?: string;
  status?: string;
}

export interface Payment {
  id: number;
  reference?: string;
  amount?: number;
  method?: string;
}

export interface ApiResponse<T> {
  data: T;
}

