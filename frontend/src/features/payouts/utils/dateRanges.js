// Date range utilities for payouts

export const getDateRangeOptions = () => {
  return [
    { label: 'Last 7 Days', value: '7d' },
    { label: 'Last 30 Days', value: '30d' },
    { label: 'Last 90 Days', value: '90d' },
    { label: 'This Month', value: 'this_month' },
    { label: 'Last Month', value: 'last_month' },
    { label: 'This Year', value: 'this_year' },
    { label: 'Custom', value: 'custom' },
  ];
};

export const getDateRange = (range) => {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  switch (range) {
    case '7d':
      return { start: new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000), end: today };
    case '30d':
      return { start: new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000), end: today };
    case '90d':
      return { start: new Date(today.getTime() - 90 * 24 * 60 * 60 * 1000), end: today };
    case 'this_month':
      return {
        start: new Date(today.getFullYear(), today.getMonth(), 1),
        end: today,
      };
    case 'last_month':
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      return {
        start: lastMonth,
        end: new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0),
      };
    case 'this_year':
      return { start: new Date(today.getFullYear(), 0, 1), end: today };
    default:
      return { start: null, end: null };
  }
};
