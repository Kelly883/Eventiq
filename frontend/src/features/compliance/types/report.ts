import type { ComplianceClassification } from './audit';

export type ReportType =
  | 'data_access'
  | 'user_activity'
  | 'payment_audit'
  | 'refund_audit'
  | 'data_retention'
  | 'incident_report';

export type ReportFormat = 'pdf' | 'csv' | 'json';

export type ReportStatus = 'queued' | 'generating' | 'completed' | 'failed';

export interface ComplianceReport {
  readonly id: string;
  readonly reportType: ReportType;
  readonly startDate: string;
  readonly endDate: string;
  readonly classification: ComplianceClassification;
  readonly format: ReportFormat;
  readonly status: ReportStatus;
  readonly createdAt: string;
  readonly createdBy: string;
}

export interface ComplianceChecklistItem {
  readonly name: string;
  readonly status: 'green' | 'amber' | 'red';
  readonly description: string;
  readonly lastChecked: string;
}
