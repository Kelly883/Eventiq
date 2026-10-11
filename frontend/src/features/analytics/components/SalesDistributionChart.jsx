import React from 'react';
import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
  Label,
  CartesianGrid,
} from 'recharts';

const SalesDistributionChart = () => {
  const sampleData = [
    { label: 'General Admission', revenue: 5200, color: '#6366f1' },
    { label: 'VIP', revenue: 3800, color: '#8b5cf6' },
    { label: 'Premium', revenue: 2500, color: '#ec4899' },
    { label: 'Platinum', revenue: 1200, color: '#f472b6' },
    { label: 'Enterprise', revenue: 800, color: '#10b981' },
  ];

  const totalRevenue = sampleData.reduce(
    (sum, item) => sum + item.revenue,
    0
  );

  return (
    <ResponsiveContainer width="100%" height={300}>
      <PieChart data={sampleData} margin={{ top: 5, right: 15, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
        <Tooltip
          contentStyle={{
            backgroundColor: '#1e293b',
            color: '#f8fafc',
            borderRadius: '6px',
            fontSize: '12px',
            border: 'none',
          }}
        />
        <Pie
          dataKey="revenue"
          fill={({ index }) => sampleData[index].color}
          labelPosition="insideEnd"
          labelLine={false}
          innerRadius={50}
          outerRadius={120}
        />
        <Label
          dataKey="label"
          fontSize={12}
          fill="#1e293b"
          position="insideEnd"
        />
      </PieChart>
    </ResponsiveContainer>
  );
};

export default SalesDistributionChart;