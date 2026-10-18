import React from 'react';
import { formatCurrency } from '../../lib/currencyUtils';

interface CartItem {
  readonly ticketTierId: string;
  readonly quantity: number;
  readonly unitPrice: number;
  readonly eventTitle: string;
  readonly eventDate: string;
  readonly eventVenue: string;
  readonly tierName: string;
  readonly currency?: string;
}

interface CartItemRowProps {
  item: CartItem;
  quantity: number;
  onQuantityChange: (newQuantity: number) => void;
  onRemove: () => void;
}

const getDecrementClass = (quantity: number) =>
  quantity <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-slate-50 text-slate-600 hover:bg-slate-100';

const getIncrementClass = (quantity: number) =>
  quantity === 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-slate-50 text-slate-600 hover:bg-slate-100';

const CartItemRow: React.FC<CartItemRowProps> = ({ item, quantity, onQuantityChange, onRemove }) => {
  const handleIncrement = () => onQuantityChange(quantity + 1);
  const handleDecrement = () => quantity > 1 ? onQuantityChange(quantity - 1) : onQuantityChange(0);

  const lineTotal = quantity * item.unitPrice;
  const currency = item.currency || 'USD';

  return (
    <div className="flex items-center justify-between px-4 py-3 border-b last:border-0 bg-white">
      <div className="flex items-center gap-4">
        <span className="text-slate-600 text-sm tier-name">
          {item.tierName}
        </span>
        <div className="flex-1 truncate">
          <p className="text-slate-800 text-sm line-clamp-1">
            {item.eventTitle}
          </p>
          <p className="text-slate-500 text-xs">
            {item.eventDate} · {item.eventVenue}
          </p>
        </div>
      </div>

      <div className="flex items-center gap-2">
        <button
          onClick={handleDecrement}
          disabled={quantity <= 1}
          className={`p-2 rounded-md ${getDecrementClass(quantity)}`}
          aria-label="Decrease quantity"
        >
          -
        </button>
        <span className="text-slate-800 font-medium text-sm min-w-[30px] text-center">
          {quantity}
        </span>
        <button
          onClick={handleIncrement}
          className={`p-2 rounded-md ${getIncrementClass(quantity)}`}
          aria-label="Increase quantity"
        >
          +
        </button>
      </div>

      <span className="text-slate-900 font-medium text-sm">
        {formatCurrency(lineTotal, currency)}
      </span>

      <button
        onClick={onRemove}
        className="ml-3 text-red-500 text-sm hover:text-red-700"
        aria-label="Remove item"
      >
        Remove
      </button>
    </div>
  );
};

export default CartItemRow;