declare module '@/features/analytics/components/MetricCard' {
  import React from 'react';
  
  interface MetricCardProps {
    value: string;
    label: string;
    trendDirection?: 'up' | 'down' | 'neutral';
    trendPercentage?: string;
    loading?: boolean;
  }
  
  export const MetricCard: React.FC<MetricCardProps>;
  export default MetricCard;
}

declare module '@/features/analytics/components/LiveIndicator' {
  import React from 'react';
  
  export const LiveIndicator: React.FC<Record<string, never>>;
  export default LiveIndicator;
}

declare module 'react-hot-toast' {
  import React from 'react';
  
  export interface ToastOptions {
    duration?: number;
    position?: string;
    style?: React.CSSProperties;
    className?: string;
  }
  
  interface Toast {
    (message: string, options?: ToastOptions): string;
    success: (message: string, options?: ToastOptions) => string;
    error: (message: string, options?: ToastOptions) => string;
    loading: (message: string, options?: ToastOptions) => string;
    dismiss: (id?: string) => void;
    promise: <T>(promise: Promise<T>, messages: { loading: string; success: string; error: string }) => Promise<T>;
    Toaster: React.FC<{ position?: string; toastOptions?: ToastOptions }>;
  }
  
  const toast: Toast;
  
  export { toast as success, toast as error, toast as loading, toast as dismiss, toast as promise, toast as Toaster };
  export default toast;
}