import React from 'react';

const TicketTierCard = ({ tier, onUpdate, onRemove, disabled = false, errors = {}, index = 0 }) => {
  if (!tier) return null;

  const nameError = errors[`tierName-${index}`];
  const priceError = errors[`tierPrice-${index}`];
  const quantityError = errors[`tierQuantity-${index}`];

  return (
    <div className="ticket-tier-card" data-testid={`ticket-tier-${index}`}>
      <div className="ticket-tier-card__field">
        <label htmlFor={`tier-name-${index}`} className="ticket-tier-card__label">Tier Name *</label>
        <input
          id={`tier-name-${index}`}
          value={tier.name}
          onChange={(e) => onUpdate?.(index, 'name', e.target.value)}
          disabled={disabled}
          placeholder="e.g. Regular, VIP"
          className={`ticket-tier-card__input ${nameError ? 'ticket-tier-card__input--error' : ''}`}
          aria-invalid={Boolean(nameError)}
          aria-describedby={nameError ? `tier-name-error-${index}` : undefined}
        />
        {nameError && <p id={`tier-name-error-${index}`} className="ticket-tier-card__error" role="alert">{nameError}</p>}
      </div>

      <div className="ticket-tier-card__field">
        <label htmlFor={`tier-price-${index}`} className="ticket-tier-card__label">Price</label>
        <input
          id={`tier-price-${index}`}
          type="number"
          min="0"
          step="0.01"
          value={tier.price}
          onChange={(e) => onUpdate?.(index, 'price', e.target.value)}
          disabled={disabled}
          placeholder="25.00"
          className={`ticket-tier-card__input ${priceError ? 'ticket-tier-card__input--error' : ''}`}
          aria-invalid={Boolean(priceError)}
          aria-describedby={priceError ? `tier-price-error-${index}` : undefined}
        />
        {priceError && <p id={`tier-price-error-${index}`} className="ticket-tier-card__error" role="alert">{priceError}</p>}
      </div>

      <div className="ticket-tier-card__field">
        <label htmlFor={`tier-quantity-${index}`} className="ticket-tier-card__label">Quantity</label>
        <input
          id={`tier-quantity-${index}`}
          type="number"
          min="1"
          value={tier.quantity}
          onChange={(e) => onUpdate?.(index, 'quantity', e.target.value)}
          disabled={disabled}
          placeholder="100"
          className={`ticket-tier-card__input ${quantityError ? 'ticket-tier-card__input--error' : ''}`}
          aria-invalid={Boolean(quantityError)}
          aria-describedby={quantityError ? `tier-quantity-error-${index}` : undefined}
        />
        {quantityError && <p id={`tier-quantity-error-${index}`} className="ticket-tier-card__error" role="alert">{quantityError}</p>}
      </div>

      <div className="ticket-tier-card__actions">
        <button
          type="button"
          onClick={() => onRemove?.(index)}
          disabled={disabled}
          className="ticket-tier-card__remove"
          aria-label={`Remove ${tier.name || 'ticket tier'}`}
        >
          Remove
        </button>
      </div>
    </div>
  );
};

export default TicketTierCard;
