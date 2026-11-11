import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import { vi } from 'vitest';
import PaymentForm from '../PaymentForm';

expect.extend(toHaveNoViolations);

const mockOnSuccess = vi.fn();
const mockOnError = vi.fn();

describe('PaymentForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockOnSuccess.mockReset();
    mockOnError.mockReset();
  });

  it('renders email input and payment button', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={false}
        amount={11400}
        currency="USD"
        email="buyer@example.com"
      />
    );
    
    expect(screen.getByLabelText(/Email address/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Pay with Paystack/i })).toBeInTheDocument();
  });

  it('shows both gateway options', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={false}
        amount={11400}
        currency="USD"
        gateway="paystack"
      />
    );
    
    expect(screen.getByRole('radio', { name: /Pay with Paystack/i })).toBeInTheDocument();
    expect(screen.getByRole('radio', { name: /Pay with Flutterwave/i })).toBeInTheDocument();
  });

  it('disables form when isLoading is true', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={true}
        amount={11400}
        currency="USD"
      />
    );
    
    expect(screen.getByLabelText(/Email address/i)).toBeDisabled();
    expect(screen.getByRole('button', { name: /Processing…/i })).toBeDisabled();
  });

  it('shows loading state on button', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={true}
        amount={11400}
        currency="USD"
      />
    );
    
    expect(screen.getByRole('button', { name: /Processing…/i })).toBeInTheDocument();
  });

  it('passes accessibility audit', async () => {
    const { container } = render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={false}
        amount={11400}
        currency="USD"
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes accessibility audit in loading state', async () => {
    const { container } = render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={true}
        amount={11400}
        currency="USD"
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('uses default email from props', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={false}
        amount={11400}
        currency="USD"
        email="buyer@example.com"
      />
    );
    
    expect(screen.getByLabelText(/Email address/i)).toHaveValue('buyer@example.com');
  });

  it('uses default gateway from props', () => {
    render(
      <PaymentForm
        onSuccess={mockOnSuccess}
        onError={mockOnError}
        isLoading={false}
        amount={11400}
        currency="USD"
        gateway="flutterwave"
      />
    );
    
    expect(screen.getByRole('radio', { name: /Pay with Flutterwave/i })).toBeChecked();
  });
});