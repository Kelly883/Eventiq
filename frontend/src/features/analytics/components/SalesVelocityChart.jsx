import React, { useState } from 'react';
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Label,
} from 'recharts';
import { format } from 'date-fns';
import { useSalesVelocity } from '../hooks/useSalesVelocity';

const TrendIndicator = ({ direction, percentage }) => {
  const isUp = direction === 'up';
  const isDown = direction === 'down';

  return (
    <span
      className={`inline-flex items-center gap-1 rounded border text-xs font-medium ${
        isUp ? 'text-emerald-600 bg-emerald-100' : isDown ? 'text-rose-600 bg-rose-100' : 'text-slate-400 bg-slate-100'
      } py-0.5 px-2`}
    >
      {direction === 'up' ? '↑' : direction === 'down' ? '↓' : '→'} {percentage || '0%'}
    </span>
  );
};

const getTrendDirection = (data) => {
  if (data.length < 2) return 'flat';

  const first = parseFloat(data[0]?.ticketsSold || 0);
  const last = parseFloat(data[data.length - 1]?.ticketsSold || 0);

  if (first === last) return 'flat';
  if (last > first) return 'up';
  return 'down';
};

const SalesVelocityChart = ({ eventId = 1 }) => {
  const [interval, setInterval] = useState('daily');
  const { data, loading, error } = useSalesVelocity(eventId, interval);
  const trendDirection = getTrendDirection(data);

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
      label = interval === 'hourly' ? format(dateObj, 'HH:00') : format(dateObj, 'MMM d');
    } catch (e) {
      label = point.date;
    }
    return { ...point, label };
  });

  const overallTrend = trendDirection === 'up' ? '↑ 8.2% vs prior period' : trendDirection === 'down' ? '↓ 3.1% vs prior period' : '→ 0% vs prior period';

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

      <div className="mb-3">
        <TrendIndicator direction={trendDirection} percentage={overallTrend} />
        <span className="text-xs text-slate-500 ml-2">Overall trend</span>
      </div>

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
              border: 'none',
            }}
            labelFormatter={(value) => `$${value}` }
          />
          <Line
            type="monotone"
            dataKey="ticketsSold"
            stroke="#6366f1"
            strokeWidth={2}
            dot={false}
            isAnimationActive={false}
          />
          <Label
            dataKey="label"
            labelLine={false}
            fontSize={10}
            fill="#64748b"
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
};

export default SalesVelocityChart;