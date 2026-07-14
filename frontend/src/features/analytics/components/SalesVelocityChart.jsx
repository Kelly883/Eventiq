import React, { useState } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';
import { format } from 'date-fns';
import { useSalesVelocity } from '../hooks/useSalesVelocity';

/**
 * Renders ticket sales velocity over time with pre-aggregated server intervals.
 * Features:
 *  - Server-side pre-aggregation toggle (Daily vs Hourly)
 *  - Disabled animations to optimize high-load rendering layout calculations.
 */
const SalesVelocityChart = ({ eventId = 1 }) => {
  const [interval, setInterval] = useState('daily');
  const { data, loading, error } = useSalesVelocity(eventId, interval);

  if (loading) {
    return (
      <div className="flex h-[300px] items-center justify-center rounded-lg border border-slate-100 bg-white p-6 shadow-sm">
        <span className="text-slate-500 animate-pulse text-sm">Aggregating sales data on server...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex h-[300px] items-center justify-center rounded-lg border border-rose-100 bg-rose-50/50 p-6 text-rose-600">
        <span className="text-sm font-medium">Unable to load pre-aggregated sales velocity.</span>
      </div>
    );
  }

  const renderChart = () => {
    if (!data || data.length === 0) {
      return (
        <div className="flex h-[240px] items-center justify-center text-slate-400 text-sm">
          No sales velocity recorded for this event
        </div>
      );
    }

    const chartData = data.map((point) => {
      let label = '';
      try {
        const dateObj = new Date(point.date);
        label = interval === 'hourly' 
          ? format(dateObj, 'HH:00') 
          : format(dateObj, 'MMM d');
      } catch (e) {
        label = point.date;
      }
      return {
        ...point,
        label,
      };
    });

    return (
      <ResponsiveContainer width="100%" height={240}>
        <LineChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
          <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
          <XAxis 
            dataKey="label" 
            stroke="#94a3b8" 
            fontSize={11}
            tickLine={false}
          />
          <YAxis 
            stroke="#94a3b8" 
            fontSize={11}
            tickLine={false}
            axisLine={false}
          />
          <Tooltip 
            contentStyle={{ 
              backgroundColor: '#1e293b', 
              color: '#f8fafc', 
              borderRadius: '6px',
              fontSize: '12px',
              border: 'none'
            }} 
          />
          <Line 
            type="monotone" 
            dataKey="ticketsSold" 
            stroke="#6366f1" 
            strokeWidth={2} 
            dot={false} 
            isAnimationActive={false} // Performance optimization for high-load charts
          />
        </LineChart>
      </ResponsiveContainer>
    );
  };

  return (
    <div className="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
      <div className="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 className="text-base font-semibold text-slate-800">Sales Velocity</h3>
          <p className="text-xs text-slate-500">Real-time ticket conversion momentum</p>
        </div>
        
        {/* Server-side pre-aggregation level selector */}
        <div className="flex bg-slate-100 rounded-lg p-1 text-xs font-medium self-start sm:self-center">
          <button
            onClick={() => setInterval('daily')}
            className={`px-3 py-1.5 rounded-md transition-all ${
              interval === 'daily'
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            Daily Buckets
          </button>
          <button
            onClick={() => setInterval('hourly')}
            className={`px-3 py-1.5 rounded-md transition-all ${
              interval === 'hourly'
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-800'
            }`}
          >
            Hourly Buckets
          </button>
        </div>
      </div>

      {renderChart()}
    </div>
  );
};

export default SalesVelocityChart;
