import React, { useMemo } from 'react';
import './AuthComponents.css';

const PasswordStrengthMeter = ({ password = '' }) => {
  const getStrength = useMemo(() => {
    if (!password) return { level: 0, label: '', color: 'transparent' };

    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[^a-zA-Z0-9]/.test(password);
    const length = password.length;
    const variety = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;

    if (length >= 8 && variety >= 3) {
      return { level: 3, label: 'Strong', color: 'var(--strength-strong, #16a34a)' };
    }
    if (length >= 6 && variety >= 2) {
      return { level: 2, label: 'Medium', color: 'var(--strength-medium, #d97706)' };
    }
    return { level: 1, label: 'Weak', color: 'var(--strength-weak, #dc2626)' };
  }, [password]);

  const { level, label, color } = getStrength();
  const percentage = password ? (level / 3) * 100 : 0;

  return (
    <div className="auth-strength" aria-live="polite" data-testid="password-strength-meter">
      <div className="auth-strength__bar" role="progressbar" aria-valuenow={level} aria-valuemin="0" aria-valuemax="3" aria-label={`Password strength: ${label}`}>
        <div
          className="auth-strength__fill"
          style={{ width: `${percentage}%`, backgroundColor: color }}
        />
      </div>
      {label && (
        <span className="auth-strength__label" style={{ color }}>
          {label}
        </span>
      )}
    </div>
  );
};

export default PasswordStrengthMeter;