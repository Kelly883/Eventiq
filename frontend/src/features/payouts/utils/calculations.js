// Calculation utilities for payouts

export const calculatePlatformFee = (amount, percentage) => {
  return (amount * percentage) / 100;
};

export const calculateOrganizerShare = (amount, platformFee) => {
  return amount - platformFee;
};

export const calculateNetAmount = (grossAmount, fees, taxes) => {
  return grossAmount - fees - taxes;
};

export const sumPayoutsByStatus = (payouts, status) => {
  return payouts
    .filter(p => p.status === status)
    .reduce((sum, p) => sum + p.amount, 0);
};
