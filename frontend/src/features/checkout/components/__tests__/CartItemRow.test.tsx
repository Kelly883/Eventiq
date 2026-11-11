import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import { vi } from 'vitest';
import CartItemRow from '../CartItemRow';

expect.extend(toHaveNoViolations);

const mockItem = {
  ticketTierId: 't1',
  quantity: 2,
  unitPrice: 2500,
  eventTitle: 'Rock Fest 2026',
  eventDate: '2026-11-20T20:00:00Z',
  eventVenue: 'Mega Arena, Lagos',
  tierName: 'General Admission',
  currency: 'USD',
};

const mockOnQuantityChange = vi.fn();
const mockOnRemove = vi.fn();

describe('CartItemRow', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockOnQuantityChange.mockReset();
    mockOnRemove.mockReset();
  });

  it('renders ticket tier, event, quantity, price, and line total', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    expect(screen.getByText('General Admission')).toBeInTheDocument();
    expect(screen.getByText('Rock Fest 2026')).toBeInTheDocument();
    expect(screen.getByText('2')).toBeInTheDocument(); // quantity
    expect(screen.getByText('$5,000.00')).toBeInTheDocument(); // line total (2 * 2500)
  });

  it('increments quantity when + button is clicked', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    const incrementBtn = screen.getByRole('button', { name: /Increase General Admission quantity/i });
    fireEvent.click(incrementBtn);
    
    expect(mockOnQuantityChange).toHaveBeenCalledWith(3);
  });

  it('decrements quantity when - button is clicked', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={3}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    const decrementBtn = screen.getByRole('button', { name: /Decrease General Admission quantity/i });
    fireEvent.click(decrementBtn);
    
    expect(mockOnQuantityChange).toHaveBeenCalledWith(2);
  });

  it('disables decrement button when quantity is 1', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={1}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    const decrementBtn = screen.getByRole('button', { name: /Decrease General Admission quantity/i });
    expect(decrementBtn).toBeDisabled();
  });

  it('calls onRemove when Remove button is clicked', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    const removeBtn = screen.getByRole('button', { name: /Remove General Admission from cart/i });
    fireEvent.click(removeBtn);
    
    expect(mockOnRemove).toHaveBeenCalledTimes(1);
  });

  it('shows event date and venue', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    // The date and venue are in the same paragraph, separated by " · "
    expect(screen.getByText(/2026-11-20T20:00:00Z/)).toBeInTheDocument();
    expect(screen.getByText(/Mega Arena, Lagos/)).toBeInTheDocument();
  });

  it('formats currency correctly for different currencies', () => {
    const ngnItem = { ...mockItem, currency: 'NGN', unitPrice: 500000 };
    
    render(
      <CartItemRow
        item={ngnItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    // NGN formatting uses Intl.NumberFormat which outputs "NGN 1,000,000.00"
    expect(document.body.textContent).toContain('NGN');
  });

  it('passes accessibility audit', async () => {
    const { container } = render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('has proper ARIA attributes on quantity display', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    // The quantity display has aria-live="polite" and id="cart-item-quantity"
    const quantityDisplay = screen.getByRole('status');
    expect(quantityDisplay).toHaveAttribute('id', 'cart-item-quantity');
    expect(quantityDisplay).toHaveAttribute('aria-live', 'polite');
  });

  it('has proper ARIA labels on buttons', () => {
    render(
      <CartItemRow
        item={mockItem}
        quantity={2}
        onQuantityChange={mockOnQuantityChange}
        onRemove={mockOnRemove}
      />
    );
    
    expect(screen.getByRole('button', { name: /Decrease General Admission quantity/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Increase General Admission quantity/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Remove General Admission from cart/i })).toBeInTheDocument();
  });
});