import React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';

const ConversionFunnelChart = () => {
  const funnelData = [
    { stage: 'Visitors', conversions: 5000 },
    { stage: 'Sign-ups', conversions: 2800 },
    { stage: 'Qualified', conversions: 1800 },
    { stage: 'Proposals', conversions: 950 },
    { stage: 'Closed-Won', conversions: 420 },
  ];

  const maxConversions = Math.max(...funnelData.map((d) => d.conversions));

  return (
    <ResponsiveContainer width="100%" height={300}>
      <BarChart data={funnelData} margin={{ top: 5, right: 15, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
        <XAxis
          dataKey="stage"
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
        {funnelData.map((item, index) => (
          <Bar
            key={item.stage}
            dataKey="conversions"
            fill={index % 2 === 0 ? '#6366f1' : '#8b5cf6'}
            strokeWidth={1}
            barSize={20}
            isAnimationActive={false}
          />
        ))}
      </BarChart>
    </ResponsiveContainer>
  );
};

export default ConversionFunnelChart;