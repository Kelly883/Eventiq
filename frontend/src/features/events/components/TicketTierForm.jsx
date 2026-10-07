import React, { useState, useCallback } from 'react';
import TicketTierCard from './TicketTierCard';

const TicketTierForm = ({ tiers = [], onChange, disabled = false, errors = {} }) => {
  const [internalTiers, setInternalTiers] = useState(() =>
    tiers.length
      ? tiers.map((t) => ({
          id: t.id,
          name: t.name || '',
          price: t.price != null ? String(t.price) : '',
          quantity: t.quantity != null ? String(t.quantity) : '',
        }))
      : [{ id: null, name: '', price: '', quantity: '' }]
  );

  const emitChange = useCallback(
    (updated) => {
      setInternalTiers(updated);
      onChange?.(updated);
    },
    [onChange]
  );

  const handleUpdate = useCallback(
    (index, field, value) => {
      const updated = internalTiers.map((t, i) => (i === index ? { ...t, [field]: value } : t));
      emitChange(updated);
    },
    [emitChange, internalTiers]
  );

  const handleRemove = useCallback(
    (index) => {
      if (internalTiers.length <= 1) return;
      emitChange(internalTiers.filter((_, i) => i !== index));
    },
    [emitChange, internalTiers.length]
  );

  const handleAdd = useCallback(() => {
    if (internalTiers.length >= 10) return;
    emitChange([...internalTiers, { id: null, name: '', price: '', quantity: '' }]);
  }, [emitChange, internalTiers.length]);

  return (
    <div className="ticket-tier-form">
      <div className="ticket-tier-form__header">
        <div>
          <h3 className="ticket-tier-form__title">Ticket Tiers</h3>
          <p className="ticket-tier-form__subtitle">Add ticket tiers with different prices and quantities.</p>
        </div>
        <span className="ticket-tier-form__count">{internalTiers.length}/10</span>
      </div>

      <div className="ticket-tier-form__list">
        {internalTiers.map((tier, i) => (
          <TicketTierCard
            key={tier.id ?? i}
            tier={tier}
            index={i}
            onUpdate={handleUpdate}
            onRemove={handleRemove}
            disabled={disabled}
            errors={errors}
          />
        ))}
      </div>

      <button
        type="button"
        onClick={handleAdd}
        disabled={disabled || internalTiers.length >= 10}
        className="ticket-tier-form__add"
      >
        + Add Tier
      </button>
    </div>
  );
};

export default TicketTierForm;
