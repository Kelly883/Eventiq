export const formatCurrency = (amount, currency = 'USD') => {
  if (amount === null || amount === undefined) return '—';
  try {
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency,
    }).format(amount);
  } catch {
    return String(amount);
  }
};

export const formatDate = (value) => {
  if (!value) return '—';
  const d = new Date(value);
  return isNaN(d.getTime()) ? '—' : d.toLocaleDateString();
};

export const statusLabel = (status) => status ?? '—';
export const roleLabel = (role) => role ?? '—';

export const auditLogDescription = (action, entity) => {
  if (!action && !entity) return 'Audit';
  return `${entity ?? 'Entity'}: ${action ?? 'action'}`;
};

