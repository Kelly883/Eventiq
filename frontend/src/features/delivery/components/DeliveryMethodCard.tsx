import React from 'react';

/**
 * DeliveryMethodCard – Shows a single delivery method (email, SMS, or dashboard)
 * with its current status.
 *
 * Used on DeliverySettingsPage and TicketStatusPage.
 */
interface DeliveryMethodCardProps {
  method: 'email' | 'sms' | 'dashboard';
  isEnabled: boolean;
  isVerified: boolean;
  recipient: string;
  onToggle?: (enabled: boolean) => void;
  onVerify?: () => void;
  className?: string;
}

const MethodIcon = ({
  method,
  isVerified,
}: {
  method: 'email' | 'sms' | 'dashboard';
  isVerified: boolean;
}) => {
  const baseClass = 'w-6 h-6 rounded-md flex-shrink-0';
  const verifiedClass = 'bg-green-100 text-green-600';
  const unverifiedClass = 'bg-slate-200 text-slate-400';

  const icons = {
    email: isVerified ? (
      <svg className={`${verifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
        <polyline points="20 6 9 17 4 12" />
      </svg>
    ) : (
      <svg className={`${unverifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <circle cx="9" cy="21" r="1" />
        <circle cx="20" cy="21" r="1" />
        <line x1="1" y1="1" x2="23" y2="23" />
      </svg>
    ),
    sms: isVerified ? (
      <svg className={`${verifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
        <polyline points="20 6 9 17 4 12" />
      </svg>
    ) : (
      <svg className={`${unverifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <circle cx="9" cy="21" r="1" />
        <circle cx="20" cy="21" r="1" />
        <line x1="1" y1="1" x2="23" y2="23" />
      </svg>
    ),
    dashboard: isVerified ? (
      <svg className={`${verifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
      </svg>
    ) : (
      <svg className={`${unverifiedClass} ${baseClass}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
      </svg>
    ),
  };

  return icons[method];
};

export const DeliveryMethodCard: React.FC<DeliveryMethodCardProps> = ({
  method,
  isEnabled,
  isVerified,
  recipient,
  onToggle,
  onVerify,
  className,
}) => {
  const handleToggle = () => {
    if (onToggle) onToggle(!isEnabled);
  };

  const handleVerify = () => {
    if (onVerify) onVerify();
  };

  const methodDisplay = {
    email: isEnabled ? 'Email' : 'Email (disabled)',
    sms: isEnabled ? 'SMS' : 'SMS (disabled)',
    dashboard: isEnabled ? 'Dashboard' : 'Dashboard (disabled)',
  }[method];

  const recipientLabel = method === 'email' ? 'Email' : 'Phone';

  const isMethodDisabled = !isEnabled;
  const statusBg = isMethodDisabled
    ? 'slate-200'
    : isVerified
      ? 'green-100'
      : 'warning-100';

  const statusText = isMethodDisabled
    ? 'Disabled'
    : isVerified
      ? 'Verified'
      : 'Unverified';

  return (
    <div
      className={`flex items-center gap-3 px-4 py-3 bg-white border border-slate-100 rounded-xl ${className}`}
    >
      <div className="w-10 h-10 flex-shrink-0 rounded-md flex items-center justify-center">
        {MethodIcon({ method, isVerified })}
      </div>

      <div className="flex-1 min-w-0">
        <p className="font-medium text-slate-900 truncate">{methodDisplay}</p>
        <p className="text-slate-500 text-sm truncate">
          {recipientLabel}: {recipient}
        </p>
      </div>

      <div className="flex items-center gap-2">
        <span
          className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${isMethodDisabled ? 'bg-slate-100 text-slate-600' : isVerified ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'}`}
        >
          {statusText}
        </span>

        {isEnabled && (
          <button
            onClick={handleToggle}
            className="p-1 rounded-md hover:bg-slate-100 transition-colors"
            aria-label={`Toggle ${method} delivery method`}
          >
            <svg
              className="w-4 h-4"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              {isVerified ? (
                <path d="M5 12h14M12 5l7 7-7 7" />
              ) : (
                <path d="M12 20V10M5 12h14" />
              )}
            </svg>
          </button>
        )}

        {!isVerified && isEnabled && (
          <button
            onClick={handleVerify}
            className="px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 rounded-md transition-colors"
            aria-label="Verify delivery method"
          >
            Verify
          </button>
        )}
      </div>
    </div>
  );
};

export default DeliveryMethodCard;
