import React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Label,
} from 'recharts';

const RevenueBreakdownChart = () => {
  const sampleData = [
    { tier: 'General Admission', revenue: 5200 },
    { tier: 'VIP', revenue: 3800 },
    { tier: 'Premium', revenue: 2500 },
    { tier: 'Platinum', revenue: 1200 },
  ];

  const totalRevenue = sampleData.reduce((sum, item) => sum + item.revenue, 0);

  return (
    <ResponsiveContainer width="100%" height={300}>
      <BarChart data={sampleData} margin={{ top: 5, right: 15, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
        <XAxis
          dataKey="tier"
          stroke="#94a3b8"
          fontSize={11}
          tickLine={false}
          axisLine={false}
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
        />
        <Bar
          dataKey="revenue"
          fill="#6366f1"
          stroke="#6366f1"
          strokeWidth={1}
          barSize={12}
          isAnimationActive={false}
        />
        <Label
          dataKey="revenue"
          position="end"
          fontSize={10}
          fill="#64748b"
          y={-8}
        />
      </BarChart>
    </ResponsiveContainer>
  );
};

export default RevenueBreakdownChart;