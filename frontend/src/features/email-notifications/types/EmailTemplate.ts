export interface EmailTemplate {
  readonly id: string;
  readonly name: string;
  readonly type: string;
  readonly subject: string;
  readonly fromName: string | null;
  readonly fromEmail: string | null;
  readonly htmlBody: string;
  readonly mjmlBody: string;
  readonly variables: string[];
  readonly isActive: boolean;
  readonly publishedAt: string | null;
  readonly version: string | null;
  readonly category: string | null;
  readonly description: string | null;
  readonly previewHtml: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}
