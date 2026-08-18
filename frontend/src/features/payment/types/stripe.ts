export interface StripeElementsOptions {
  readonly clientSecret: string;
  readonly appearance?: Record<string, unknown>;
}

export interface StripeError {
  readonly code: string;
  readonly message: string;
}
