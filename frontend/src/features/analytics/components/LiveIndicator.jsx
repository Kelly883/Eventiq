import React from 'react';
import { useReducedMotion } from '@/features/dashboard/hooks/useReducedMotion';

const LiveIndicator = () => {
  const reducedMotion = useReducedMotion();

  return (
    <span
      className={`inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border border-emerald-200 bg-emerald-100 text-emerald-600 text-xs font-medium ${reducedMotion ? '' : 'animate-pulse'}`}
      aria-live="polite"
      aria-label="Live"
    >
      <span className="w-1.5 h-1.5 bg-emerald-500 rounded-full" aria-hidden="true" />
      Live
    </span>
  );
};

export default LiveIndicator;