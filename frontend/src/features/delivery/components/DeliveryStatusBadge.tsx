import React from 'react';

/**
 * DeliveryStatusBadge – A small component that displays the delivery status
 * with color coding.
 *
 * Status mapping:
 *   pending  -> amber (warning)
 *   sent     -> green (success)
 *   failed   -> red (error)
 *   viewed   -> blue (primary/accent)
 *   bounced  -> orange (custom)
 */
interface DeliveryStatusBadgeProps {
  status: 'pending' | 'sent' | 'failed' | 'viewed' | 'bounced';
  /** Optional size variant: 'sm' | 'md' | 'lg' */
  size?: 'sm' | 'md' | 'lg';
  /** Optional className override */
  className?: string;
}

const statusColors = {
  pending: 'warning',    // #FFDA6B (amber)
  sent: 'success',       // #4ECDC4 (green)
  failed: 'error',       // #FF6B6B (red)
  viewed: 'primary',     // #FF6B6B (or we could use a blue)
  bounced: 'warning',    // #FFDA6B (amber/orange)
};

const statusLabels = {
  pending: 'Pending',
  sent: 'Sent',
  failed: 'Failed',
  viewed: 'Viewed',
  bounced: 'Bounced',
};

const getBadgeClass = (status: keyof typeof statusColors, size: 'sm' | 'md' | 'lg'): string => {
  const sizeClass = size === 'sm' ? 'h-6 px-2 text-xs' : size === 'lg' ? 'h-8 px-4 text-sm' : 'h-7 px-3 text-sm';
  return `flex items-center rounded-full ${sizeClass} font-medium ${statusColors[status]}`;
};

export const DeliveryStatusBadge: React.FC<DeliveryStatusBadgeProps> = ({
  status,
  size = 'md',
  className,
}) => {
  const bgColor = statusColors[status as keyof typeof statusColors];
  const label = statusLabels[status];

  return (
    <span
      className={`bg-${bgColor}-100 text-${bgColor}-800 ${getBadgeClass(status, size)} ${className}`}
    >
      {label}
    </span>
  );
};

export default DeliveryStatusBadge;