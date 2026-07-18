export type OfflineOperationIdempotency = {
  client_id: string;
  op_type: string;
  entity_id: string;
  client_mutation_id: string;
};

export type OfflineEnqueueRequest<TPayload extends Record<string, any>> = {
  client_id?: string;
  op_type: string;
  entity_id: string;
  client_mutation_id: string;
  payload: TPayload;
  client_context?: Record<string, any>;
};

export type OfflineEnqueueResponse = {
  id: number;
  status: string;
  idempotency: OfflineOperationIdempotency;
};

export type ApplyDueResponse = {
  results: Array<{
    status: string;
    id?: number;
    error?: string;
    idempotency?: OfflineOperationIdempotency;
  }>;
};

