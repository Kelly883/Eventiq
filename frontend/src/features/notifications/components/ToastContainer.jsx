import React, { useState, useEffect } from 'react';
import { addToastListener } from '../../../lib/api';

export default function ToastContainer() {
  const [toasts, setToasts] = useState([]);

  useEffect(() => {
    const removeListener = addToastListener((newToast) => {
      setToasts((prev) => [...prev, newToast]);

      // Auto-dismiss logic
      const duration = newToast.duration ?? 5000;
      setTimeout(() => {
        setToasts((prev) => prev.filter((t) => t.id !== newToast.id));
      }, duration);
    });

    return () => {
      removeListener();
    };
  }, []);

  const removeToast = (id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  if (toasts.length === 0) return null;

  return (
    <div id="global-toast-container" className="fixed bottom-5 right-5 z-[9999] flex flex-col gap-3 w-full max-w-sm sm:max-w-md pointer-events-none">
      {toasts.map((toast) => {
        // Customize styling based on toast type
        let bgColor = 'bg-white';
        let borderColor = 'border-slate-200';
        let iconColor = 'text-slate-500';
        let titleColor = 'text-slate-900';
        let descColor = 'text-slate-600';
        let icon = (
          <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        );

        if (toast.type === 'error') {
          bgColor = 'bg-red-50';
          borderColor = 'border-red-200';
          iconColor = 'text-red-500';
          titleColor = 'text-red-800';
          descColor = 'text-red-700';
          icon = (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          );
        } else if (toast.type === 'warning') {
          bgColor = 'bg-amber-50';
          borderColor = 'border-amber-200';
          iconColor = 'text-amber-500';
          titleColor = 'text-amber-800';
          descColor = 'text-amber-700';
          icon = (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          );
        } else if (toast.type === 'success') {
          bgColor = 'bg-emerald-50';
          borderColor = 'border-emerald-200';
          iconColor = 'text-emerald-500';
          titleColor = 'text-emerald-800';
          descColor = 'text-emerald-700';
          icon = (
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          );
        }

        return (
          <div
            key={toast.id}
            id={`toast-${toast.id}`}
            className={`pointer-events-auto flex w-full max-w-sm rounded-xl border ${borderColor} ${bgColor} p-4 shadow-xl shadow-slate-100/40 backdrop-blur-sm transition-all duration-300 animate-slide-in-right`}
            role="alert"
          >
            <div className="flex items-start gap-3 w-full">
              <div className={`flex-shrink-0 ${iconColor}`}>
                {icon}
              </div>
              <div className="flex-1 min-w-0">
                <p className={`text-sm font-semibold ${titleColor}`}>
                  {toast.title}
                </p>
                <p className={`mt-1 text-xs leading-relaxed ${descColor}`}>
                  {toast.description}
                </p>
              </div>
              <div className="flex-shrink-0 flex pl-1">
                <button
                  id={`toast-close-${toast.id}`}
                  onClick={() => removeToast(toast.id)}
                  className="inline-flex rounded-lg p-1.5 text-slate-400 hover:text-slate-500 hover:bg-slate-100/50 transition-colors focus:outline-none"
                >
                  <span className="sr-only">Close</span>
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
