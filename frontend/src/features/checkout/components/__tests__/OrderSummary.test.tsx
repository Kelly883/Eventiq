import React from 'react';
import { render, screen } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import OrderSummary from '../OrderSummary';

expect.extend(toHaveNoViolations);

const mockItems = [
  { id: 'i1', ticketTierId: 't1', quantity: 2, unitPrice: 2500, tierName: 'General Admission' },
  { id: 'i2', ticketTierId: 't2', quantity: 1, unitPrice: 4000, tierName: 'VIP' },
];

const mockFeeBreakdown = [
  { label: 'Service fee', amount: 1200 },
  { label: 'Tax', amount: 300 },
];

describe('OrderSummary', () => {
  it('renders order summary with items, subtotal, fees, tax, and total', () => {
    render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={900}
        total={11400}
        currency="USD"
        feeBreakdown={mockFeeBreakdown}
      />
    );
    
    expect(screen.getByText('Order Summary')).toBeInTheDocument();
    expect(screen.getByText('2x General Admission')).toBeInTheDocument();
    expect(screen.getByText('1x VIP')).toBeInTheDocument();
    expect(screen.getByText('$5,000.00')).toBeInTheDocument(); // 2 * 2500
    expect(screen.getByText('$4,000.00')).toBeInTheDocument(); // 1 * 4000
    expect(screen.getByText('Subtotal')).toBeInTheDocument();
    expect(screen.getByText('$9,000.00')).toBeInTheDocument();
    expect(screen.getByText('Service fee')).toBeInTheDocument();
    expect(screen.getByText('$1,200.00')).toBeInTheDocument();
    expect(screen.getByText('Tax')).toBeInTheDocument();
    expect(screen.getByText('$300.00')).toBeInTheDocument();
    expect(screen.getByText('Total')).toBeInTheDocument();
    expect(screen.getByText('$11,400.00')).toBeInTheDocument();
  });

  it('shows loading skeleton when isLoading is true', () => {
    render(
      <OrderSummary
        items={[]}
        subtotal={0}
        fees={0}
        total={0}
        currency="USD"
        isLoading={true}
      />
    );
    
    expect(screen.getByText('Order Summary')).toBeInTheDocument();
    expect(screen.getByRole('status', { name: /loading order summary/i })).toBeInTheDocument();
  });

  it('shows error state when error is provided', () => {
    render(
      <OrderSummary
        items={[]}
        subtotal={0}
        fees={0}
        total={0}
        currency="USD"
        error="Failed to load order summary"
      />
    );
    
    expect(screen.getByText('Order Summary')).toBeInTheDocument();
    expect(screen.getByRole('alert')).toHaveTextContent(/Failed to load order summary/i);
    expect(screen.getByRole('button', { name: /Retry/i })).toBeInTheDocument();
  });

  it('shows "No items" when items array is empty', () => {
    render(
      <OrderSummary
        items={[]}
        subtotal={0}
        fees={0}
        total={0}
        currency="USD"
      />
    );
    
    expect(screen.getByText('No items')).toBeInTheDocument();
  });

  it('uses feeBreakdown when provided', () => {
    render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={900}
        total={11400}
        currency="USD"
        feeBreakdown={mockFeeBreakdown}
      />
    );
    
    expect(screen.getByText('Service fee')).toBeInTheDocument();
    expect(screen.getByText('$1,200.00')).toBeInTheDocument();
    expect(screen.getByText('Tax')).toBeInTheDocument();
    expect(screen.getByText('$300.00')).toBeInTheDocument();
  });

  it('falls back to default breakdown when feeBreakdown is not provided', () => {
    render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={900}
        total={11400}
        currency="USD"
      />
    );
    
    expect(screen.getByText('Service fee')).toBeInTheDocument();
    expect(screen.getByText('$1,500.00')).toBeInTheDocument();
    expect(screen.getByText('Tax')).toBeInTheDocument();
    expect(screen.getByText('$900.00')).toBeInTheDocument();
  });

  it('does not show tax line when tax is 0', () => {
    render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={0}
        total={10500}
        currency="USD"
        feeBreakdown={[{ label: 'Service fee', amount: 1500 }]}
      />
    );
    
    expect(screen.getByText('Service fee')).toBeInTheDocument();
    expect(screen.queryByText('Tax')).not.toBeInTheDocument();
  });

  it('formats currency with locale', () => {
    render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={900}
        total={11400}
        currency="NGN"
        locale="en-NG"
        feeBreakdown={mockFeeBreakdown}
      />
    );
    
    // NGN formatting should use ₦ symbol - check that at least one ₦ appears
    const nairaElements = screen.getAllByText(/₦/);
    expect(nairaElements.length).toBeGreaterThan(0);
  });

  it('passes accessibility audit', async () => {
    const { container } = render(
      <OrderSummary
        items={mockItems}
        subtotal={9000}
        fees={1500}
        tax={900}
        total={11400}
        currency="USD"
        feeBreakdown={mockFeeBreakdown}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes accessibility audit in loading state', async () => {
    const { container } = render(
      <OrderSummary
        items={[]}
        subtotal={0}
        fees={0}
        total={0}
        currency="USD"
        isLoading={true}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('passes accessibility audit in error state', async () => {
    const { container } = render(
      <OrderSummary
        items={[]}
        subtotal={0}
        fees={0}
        total={0}
        currency="USD"
        error="Failed to load"
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });
});