import React from 'react';
import { formatCurrency } from '../../../lib/currencyUtils';

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

const buttonBase =
  'p-2 rounded-md font-medium text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2';

const getDecrementClass = (quantity: number) =>
  quantity <= 1
    ? `${buttonBase} bg-slate-100 text-slate-300 cursor-not-allowed`
    : `${buttonBase} bg-slate-50 text-slate-600 hover:bg-slate-100`;

const getIncrementClass = (quantity: number) =>
  `${buttonBase} bg-slate-50 text-slate-600 hover:bg-slate-100`;

const CartItemRow: React.FC<CartItemRowProps> = ({
  item,
  quantity,
  onQuantityChange,
  onRemove,
}) => {
  const handleIncrement = () => onQuantityChange(quantity + 1);
  const handleDecrement = () => {
    if (quantity <= 1) return;
    onQuantityChange(quantity - 1);
  };

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
          aria-label={`Decrease ${item.tierName} quantity`}
          aria-controls="cart-item-quantity"
          className={getDecrementClass(quantity)}
        >
          -
        </button>
        <span
          id="cart-item-quantity"
          role="status"
          className="text-slate-800 font-medium text-sm min-w-[30px] text-center"
          aria-live="polite"
        >
          {quantity}
        </span>
        <button
          onClick={handleIncrement}
          aria-label={`Increase ${item.tierName} quantity`}
          aria-controls="cart-item-quantity"
          className={getIncrementClass(quantity)}
        >
          +
        </button>
      </div>

      <span className="text-slate-900 font-medium text-sm tabular-nums">
        {formatCurrency(lineTotal, currency)}
      </span>

      <button
        onClick={onRemove}
        className="ml-3 text-red-600 text-sm hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2 rounded-md p-1"
        aria-label={`Remove ${item.tierName} from cart`}
      >
        Remove
      </button>
    </div>
  );
};

export default CartItemRow;