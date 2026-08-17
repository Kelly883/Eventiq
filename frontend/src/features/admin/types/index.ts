import type { Event, Organizer } from '@/features/events/types/shared';

export interface AdminSettings {
  payoutProcessingEnabled?: boolean;
}

export interface AuditLog {
  id: number;
  action: string;
  entity: string;
  entity_id?: number;
  changes?: Record<string, unknown>;
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

export interface Payment {
  id: number;
  reference?: string;
  amount?: number;
  method?: string;
}

export interface ApiResponse<T> {
  data: T;
}

