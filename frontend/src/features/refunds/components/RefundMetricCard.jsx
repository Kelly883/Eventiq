import React from 'react';

const RefundMetricCard = ({ title, value, suffix, icon, className }) => {
  return (
    <div className={className || 'p-4 rounded-lg border border-slate-200'}> 
      <div className='text-sm text-slate-500'>{title}</div>
      <div className="text-2xl font-bold">
      <span className={suffix ? 'text-indigo-600' : ''}>{value}</span>
      {suffix && <span className="text-xs text-slate-500">{suffix}</span>}
    </div>
      <div className='text-xs text-slate-500'>{icon}</div>
    </div>
  );
};

export default RefundMetricCard;
