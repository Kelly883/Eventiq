import React from 'react';

/**
 * A beautiful, visually balanced empty-state component.
 * Follows our design principles with spacious layout, clean colors, and micro-actions.
 */
export const EmptyState = ({
  icon = '✨', // text/emoji or custom React element
  title = 'No records found',
  description = 'There are no items to display at the moment.',
  actionLabel,
  onAction,
  className = '',
}) => {
  return (
    <div className={`flex flex-col items-center justify-center p-8 md:p-12 text-center bg-white border border-slate-100 rounded-2xl shadow-sm ${className}`}>
      {/* Icon frame */}
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-3xl mb-4 border border-slate-100 shadow-sm transition-transform hover:scale-105 duration-200">
        {icon}
      </div>

      {/* Typography */}
      <h3 className="text-lg font-bold text-slate-900 tracking-tight">{title}</h3>
      <p className="mt-2 text-sm text-slate-500 max-w-sm leading-relaxed">{description}</p>

      {/* Optional action */}
      {actionLabel && onAction && (
        <button
          onClick={onAction}
          className="mt-6 inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-lg shadow-md shadow-indigo-100/60 border border-indigo-600 hover:border-indigo-700 transition-all cursor-pointer"
        >
          {actionLabel}
        </button>
      )}
    </div>
  );
};

export default EmptyState;
