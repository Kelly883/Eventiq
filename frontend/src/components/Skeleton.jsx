import React from 'react';

/**
 * Reusable Skeleton loader component for structured loading states.
 * Supports different shapes (circle, rectangle) and visual layouts (card, list, table).
 */
export const Skeleton = ({
  variant = 'text', // 'text' | 'rect' | 'circle' | 'card' | 'list' | 'table'
  className = '',
  count = 1,
  width,
  height,
}) => {
  const baseClass = 'bg-slate-200 animate-pulse rounded';

  const getStyle = () => {
    const style = {};
    if (width) style.width = width;
    if (height) style.height = height;
    return style;
  };

  if (variant === 'card') {
    return (
      <div className={`p-5 bg-white border border-slate-100 rounded-xl space-y-4 shadow-sm ${className}`}>
        <div className="flex items-center gap-3">
          <div className={`${baseClass} h-10 w-10 rounded-full`} />
          <div className="space-y-2 flex-1">
            <div className={`${baseClass} h-4 w-1/3`} />
            <div className={`${baseClass} h-3 w-1/4`} />
          </div>
        </div>
        <div className="space-y-2 pt-2">
          <div className={`${baseClass} h-3 w-full`} />
          <div className={`${baseClass} h-3 w-5/6`} />
          <div className={`${baseClass} h-3 w-2/3`} />
        </div>
      </div>
    );
  }

  if (variant === 'list') {
    return (
      <div className={`space-y-3 ${className}`}>
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-xl shadow-sm">
            <div className="flex items-center gap-3 flex-1">
              <div className={`${baseClass} h-8 w-8 rounded-lg`} />
              <div className="space-y-1.5 flex-1">
                <div className={`${baseClass} h-3.5 w-1/4`} />
                <div className={`${baseClass} h-2.5 w-1/6`} />
              </div>
            </div>
            <div className={`${baseClass} h-6 w-16 rounded`} />
          </div>
        ))}
      </div>
    );
  }

  if (variant === 'table') {
    return (
      <div className={`w-full overflow-hidden border border-slate-100 rounded-xl bg-white shadow-sm ${className}`}>
        <div className="bg-slate-50 h-11 border-b border-slate-100 flex items-center px-4 gap-4">
          <div className={`${baseClass} h-4 w-1/12`} />
          <div className={`${baseClass} h-4 w-4/12`} />
          <div className={`${baseClass} h-4 w-2/12`} />
          <div className={`${baseClass} h-4 w-2/12`} />
          <div className={`${baseClass} h-4 w-3/12`} />
        </div>
        <div className="divide-y divide-slate-100">
          {Array.from({ length: count }).map((_, i) => (
            <div key={i} className="h-14 flex items-center px-4 gap-4">
              <div className={`${baseClass} h-3.5 w-1/12`} />
              <div className={`${baseClass} h-3.5 w-4/12`} />
              <div className={`${baseClass} h-3.5 w-2/12`} />
              <div className={`${baseClass} h-3.5 w-2/12`} />
              <div className={`${baseClass} h-3.5 w-3/12`} />
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-2">
      {Array.from({ length: count }).map((_, i) => (
        <div
          key={i}
          className={`${baseClass} ${
            variant === 'circle' ? 'rounded-full' : ''
          } ${className}`}
          style={getStyle()}
        />
      ))}
    </div>
  );
};

export default Skeleton;
