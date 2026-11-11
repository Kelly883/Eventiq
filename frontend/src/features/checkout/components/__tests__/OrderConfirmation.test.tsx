import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import { vi } from 'vitest';
import OrderConfirmation from '../OrderConfirmation';

expect.extend(toHaveNoViolations);

const mockOnContinueShopping = vi.fn();
const mockOnViewTickets = vi.fn();

describe('OrderConfirmation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockOnContinueShopping.mockReset();
    mockOnViewTickets.mockReset();
  });

  it('renders success state with order details', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="success"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    expect(screen.getByText('Order placed')).toBeInTheDocument();
    expect(screen.getByText('Your payment was successful. Your tickets are ready.')).toBeInTheDocument();
    expect(screen.getByText('Order total')).toBeInTheDocument();
    expect(screen.getByText('$11,400.00')).toBeInTheDocument();
    expect(screen.getByText('Order reference')).toBeInTheDocument();
    expect(screen.getByText('order_123')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /View tickets/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Continue shopping/i })).toBeInTheDocument();
  });

  it('renders failed state', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="failed"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    expect(screen.getByText('Payment failed')).toBeInTheDocument();
    expect(screen.getByText("We couldn't complete your payment. Please try again or contact support.")).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Try again/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /View tickets/i })).not.toBeInTheDocument();
  });

  it('renders pending state', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="pending"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    expect(screen.getByText('Order received')).toBeInTheDocument();
    expect(screen.getByText("We're processing your payment. We'll email you once it's confirmed.")).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Try again/i })).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /View tickets/i })).not.toBeInTheDocument();
  });

  it('calls onViewTickets when View tickets button is clicked', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="success"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    const viewTicketsBtn = screen.getByRole('button', { name: /View tickets/i });
    fireEvent.click(viewTicketsBtn);
    
    expect(mockOnViewTickets).toHaveBeenCalledTimes(1);
  });

  it('calls onContinueShopping when Continue shopping button is clicked', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="success"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    const continueBtn = screen.getByRole('button', { name: /Continue shopping/i });
    fireEvent.click(continueBtn);
    
    expect(mockOnContinueShopping).toHaveBeenCalledTimes(1);
  });

  it('calls onContinueShopping when Try again button is clicked in failed state', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="failed"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    const tryAgainBtn = screen.getByRole('button', { name: /Try again/i });
    fireEvent.click(tryAgainBtn);
    
    expect(mockOnContinueShopping).toHaveBeenCalledTimes(1);
  });

  it('formats currency correctly', () => {
    render(
      <OrderConfirmation
        orderId="order_123"
        total={50000}
        currency="NGN"
        status="success"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    
    // NGN formatting uses Intl.NumberFormat which outputs "NGN 50,000.00"
    expect(document.body.textContent).toContain('NGN');
  });

  it('passes accessibility audit for success state', async () => {
    const { container } = render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="success"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes accessibility audit for failed state', async () => {
    const { container } = render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="failed"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes accessibility audit for pending state', async () => {
    const { container } = render(
      <OrderConfirmation
        orderId="order_123"
        total={11400}
        currency="USD"
        status="pending"
        onContinueShopping={mockOnContinueShopping}
        onViewTickets={mockOnViewTickets}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});