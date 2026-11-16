import React from 'react';
import { FraudFactors } from '../types/fraud';

interface FraudFactorsDisplayProps {
  fraudFactors: FraudFactors;
  detectionMethod: string;
}

const factorLabels: Record<keyof FraudFactors, { label: string; icon: string }> = {
  duplicateTicketDetected: {
    label: 'Duplicate ticket detected',
    icon: 'M12 5v.01M12 12v.01M12 19v.01M5 12h.01M12 12h.01M19 12h.01M1 12a11 11 0 0122 0 11 11 0 01-2-2.01M1 8a7 7 0 0014 0 7 7 0 00-2.01H9.01z',
  },
  velocityCheckFailed: {
    label: 'Velocity check failed',
    icon: 'M13 9v3a2 2 0 002 2h3a2 2 0 002-2v-3m-2-2l2 2-2 2M7.146 15.854a7 7 0 00-4.950-4.95l3.153 3.153L2 21l4.284-4.284z',
  },
  paymentPatternSuspicious: {
    label: 'Payment pattern suspicious',
    icon: 'M12 9v2m0 9v2m0-9V5a2 2 0 10-4 0v4m4 0V5a2 2 0 10-4 0v4m4 11h-2m-2-2h.01M9 12h.01M5 21a4 4 0 01-4-4V5a4 4 0 014-4h4a4 4 0 014 4v4m-5.6-3a3 3 0 006.75 0c.588 0 1.18.12 1.75.35l.35-.15a2.5 2.5 0 011.134 1.2L15 9.58l-.5 2.5a2.5 2.5 0 01-2.126 1.8l-2.21 2.15a11.955 11.955 0 01-3.088-.57l-.09-.25a7.979 7.979 0 00-.88-.35zM13 21a4 4 0 004-4V5a4 4 0 00-4-4h-4a4 4 0 00-4 4v4m-6 0a4 4 0 004-4V5a4 4 0 00-4 4h4m6-4h.01M15 3a7 7 0 017 7v4m0-16a7 7 0 01-7 7v4M5 12a4 4 0 01-4 4v4m0-16a4 4 0 014-4v4z',
  },
  deviceFingerprintMismatch: {
    label: 'Device fingerprint mismatch',
    icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
  geolocationAnomaly: {
    label: 'Geolocation anomaly',
    icon: 'M12 9v2m0 9v2m0-9V5a2 2 0 10-4 0v4m4 0V5a2 2 0 10-4 0v4m-6 6a3 3 0 110-6 3 3 0 010 6zM5 10a3 3 0 013 3h4a3 3 0 010 6h-2.138l.593 4.533a1 1 0 01-.898.7l-1.589-1.422M15 10a3 3 0 013 3h4a3 3 0 010 6h-2.138l.593 4.533a1 1 0 01-.898.7L18.456 23l-1.588-1.422A3 3 0 0115 20V10z',
  },
  cardTestingPattern: {
    label: 'Card testing pattern',
    icon: 'M12 5v.01M12 12v.01M12 19v.01M5 12h.01M12 12h.01M19 12h.01M1 12a11 11 0 0122 0 11 11 0 01-2-2.01M1 8a7 7 0 0014 0 7 7 0 00-2.01H9.01z',
  },
  highRiskPaymentMethod: {
    label: 'High risk payment method',
    icon: 'M12 5v.01M12 12v.01M12 19v.01M5 12h.01M12 12h.01M19 12h.01M1 12a11 11 0 0122 0 11 11 0 01-2-2.01M1 8a7 7 0 0014 0 7 7 0 00-2.01H9.01z',
  },
  duplicateCheckin: {
    label: 'Duplicate checkin',
    icon: 'M12 5v.01M12 12v.01M12 19v.01M5 12h.01M12 12h.01M19 12h.01M1 12a11 11 0 0122 0 11 11 0 01-2-2.01M1 8a7 7 0 0014 0 7 7 0 00-2.01H9.01z',
  },
  invalidQr: {
    label: 'Invalid QR code',
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  manualOverride: {
    label: 'Manual override',
    icon: 'M12 5v.01M12 12v.01M12 19v.01M5 12h.01M12 12h.01M19 12h.01M1 12a11 11 0 0122 0 11 11 0 01-2-2.01M1 8a7 7 0 0014 0 7 7 0 00-2.01H9.01z',
  },
};

const FraudFactorsDisplay: React.FC<FraudFactorsDisplayProps> = ({
  fraudFactors,
  detectionMethod,
}) => {
  const activeFactors = Object.entries(fraudFactors)
    .filter(([, triggered]) => triggered)
    .map(([key]) => factorLabels[key]);

  return (
    <div className="space-y-1.5 text-sm" aria-label="Fraud factors">
      {activeFactors.length > 0 ? (
        activeFactors.map(({ label, icon }, index) => (
          <div
            key={index}
            className="flex items-start gap-2"
            aria-label={`Fraud factor: ${label}`}
          >
            <svg
              className="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"
              viewBox="0 0 24 24"
              fill="currentColor"
              aria-hidden="true"
            >
              <path d={icon} />
            </svg>
            <span className="flex-1">{label}</span>
          </div>
        ))
      ) : (
        <p className="text-slate-500">No fraud factors detected</p>
      )}
    </div>
  );
};

export { FraudFactorsDisplay };