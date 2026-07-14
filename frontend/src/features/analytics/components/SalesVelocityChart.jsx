import React from 'react';
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
 * Renders ticket sales velocity over time.
 * Expects useSalesVelocity() to eventually resolve `data` as:
 *   [{ date: string | Date, ticketsSold: number }, ...]
 */
const SalesVelocityChart = () => {
  const { data, loading, error } = useSalesVelocity();

  if (loading) return <div>Loading sales velocity…</div>;
  if (error) return <div>Couldn't load sales velocity data.</div>;
  if (!data || data.length === 0) {
    return <div>Sales Velocity Chart (no data yet)</div>;
  }

  const chartData = data.map((point) => ({
    ...point,
    label: format(new Date(point.date), 'MMM d'),
  }));

  return (
    <ResponsiveContainer width="100%" height={300}>
      <LineChart data={chartData}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="label" />
        <YAxis />
        <Tooltip />
        <Line type="monotone" dataKey="ticketsSold" stroke="#6366f1" strokeWidth={2} dot={false} />
      </LineChart>
    </ResponsiveContainer>
  );
};

export default SalesVelocityChart;
