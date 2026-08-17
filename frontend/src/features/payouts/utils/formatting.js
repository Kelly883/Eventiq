// Formatting utilities for payouts

export const formatCurrency = (amount, currency = 'NGN') => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: currency,
  }).format(amount);
};

export const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    processing: 'Processing',
    completed: 'Completed',
    failed: 'Failed',
    cancelled: 'Cancelled',
  };
  return statusMap[status] || status;
};

export const formatPayoutMethod = (method) => {
  const methodMap = {
    bank_transfer: 'Bank Transfer',
    paypal: 'PayPal',
    stripe: 'Stripe',
    check: 'Check',
  };
  return methodMap[method] || method;
};
