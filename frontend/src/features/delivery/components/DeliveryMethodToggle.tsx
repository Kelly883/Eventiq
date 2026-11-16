import React from 'react';

/**
 * DeliveryMethodToggle – A toggle switch for enabling/disabling a delivery method.
 *
 * Takes method, isEnabled, and onChange callback.
 * Shows verification status and a 'Verify Now' button if unverified.
 *
 * Used on DeliverySettingsPage.
 */
interface DeliveryMethodToggleProps {
  /** Delivery method: 'email', 'sms', or 'dashboard' */
  method: 'email' | 'sms' | 'dashboard';
  /** Whether the method is currently enabled */
  isEnabled: boolean;
  /** Whether the method is verified */
  isVerified: boolean;
  /** Callback when toggle changes */
  onChange: (enabled: boolean) => void;
  /** Callback when user clicks Verify */
  onVerify?: () => void;
  /** Optional className override */
  className?: string;
}

/** Get the toggle track background classes */
const getTrackClasses = (isEnabled: boolean, isVerified: boolean): string => {
  if (!isEnabled) return 'bg-slate-200';
  if (isVerified) return 'bg-green-200';
  return 'bg-amber-100';
};

/** Get the toggle knob classes */
const getKnobClasses = (isEnabled: boolean, isVerified: boolean): string => {
  if (!isEnabled) return 'cursor-not-allowed bg-slate-100';
  if (isVerified) return 'bg-green-100 shadow-sm';
  return 'bg-amber-100 shadow-sm';
};

export const DeliveryMethodToggle: React.FC<DeliveryMethodToggleProps> = ({
  method,
  isEnabled,
  isVerified,
  onChange,
  onVerify,
  className,
}) => {
  const handleToggle = () => {
    onChange(!isEnabled);
  };

  const handleVerify = () => {
    onVerify?.();
  };

  return (
    <div className={`space-y-3 ${className}`}>
      {/* Method label */}
      <p className="font-medium text-slate-900">{method.charAt(0).toUpperCase() + method.slice(1)}</p>

      {/* Toggle switch */}
      <div
        className="
          relative
          w-full
          h-6
          rounded-full
          overflow-hidden
          cursor-pointer
          ${!isEnabled ? 'opacity-50 cursor-not-allowed' : ''}
        "
      >
        <div
          className="
            absolute
            inset-0
            bg-${getTrackClasses(isEnabled, isVerified)}
            rounded-full
            transition-all duration-200
          "
        />
        <div
          className="
            absolute
            left-0.5
            top-0.5
            w-5
            h-5
            rounded-full
            ${getKnobClasses(isEnabled, isVerified)}
            transition-transform duration-200
            ease-out
          "
          style={{
            transform: isEnabled ? `translateX(${isVerified ? '20px' : '110px'})` : 'translateX(0)',
          }}
        />
      </div>

      {/* Action buttons */}
      <div className="flex flex-col gap-2">
        {/* Verify button when unverified */}
        {!isVerified && isEnabled && (
          <button
            onClick={handleVerify}
            className="w-full px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 rounded-md transition-colors"
            aria-label="Verify delivery method"
          >
            Verify
          </button>
        )}

        {/* Disabled state message */}
        {!isEnabled && (
          <p className="text-sm text-slate-500">
            {method} delivery is currently disabled
          </p>
        )}
      </div>
    </div>
  );
};

export default DeliveryMethodToggle;
