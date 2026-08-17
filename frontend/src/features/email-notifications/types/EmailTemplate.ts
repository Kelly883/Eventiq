export interface EmailTemplate {
  readonly id: string;
  readonly name: string;
  readonly type: string;
  readonly subject: string;
  readonly htmlBody: string;
  readonly mjmlBody: string;
  readonly variables: string[];
  readonly isActive: boolean;
  readonly createdAt: string;
  readonly updatedAt: string;
}
