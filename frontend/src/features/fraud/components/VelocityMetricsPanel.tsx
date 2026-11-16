import React from 'react';
import { VelocityMetrics, PaymentMethodVelocity } from '../types/fraud';

interface VelocityMetricsPanelProps {
  velocityMetrics: VelocityMetrics;
  paymentMethodVelocity?: PaymentMethodVelocity;
}

const VelocityMetricsPanel: React.FC<VelocityMetricsPanelProps> = ({
  velocityMetrics,
  paymentMethodVelocity,
}) => {
  const formatNumber = (num: number): string =>
    new Intl.NumberFormat('en-US').format(num);

  return (
    <div className="space-y-4" aria-label="Velocity metrics">
      <div className="grid grid-cols-2 gap-4 text-sm">
        <div>
          <p className="text-slate-500">Orders in 24h</p>
          <p className="text-2xl font-bold text-slate-900">{formatNumber(velocityMetrics.ordersIn24h)}</p>
        </div>
        <div>
          <p className="text-slate-500">Total spend in 24h</p>
          <p className="text-2xl font-bold text-slate-900">${formatNumber(velocityMetrics.totalSpendIn24h)}</p>
        </div>
        <div>
          <p className="text-slate-500">Avg order value</p>
          <p className="text-2xl font-bold text-slate-900">${formatNumber(velocityMetrics.averageOrderValue)}</p>
        </div>
        <div>
          <p className="text-slate-500">Orders in last hour</p>
          <p className="text-2xl font-bold text-slate-900">{formatNumber(velocityMetrics.ordersInLastHour)}</p>
        </div>
      </div>

      {paymentMethodVelocity && (
        <div className="pt-4 border-t border-slate-200">
          <p className="text-slate-500">Payment method velocity</p>
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div>
              <p className="font-medium">Users on same card</p>
              <p>{formatNumber(paymentMethodVelocity.usersOnSameCard)}</p>
            </div>
            <div>
              <p className="font-medium">Total spend on card</p>
              <p>${formatNumber(paymentMethodVelocity.totalSpendOnCard)}</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export { VelocityMetricsPanel };