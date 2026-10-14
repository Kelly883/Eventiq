import React from 'react';

const Skeleton = ({ className, width = '100%', height = '16px', radius = '8px' }) => (
  <div
    className={`skeleton ${className || ''}`}
    style={{
      width,
      height,
      borderRadius: radius,
      background: '#f0f0f0',
      animation: 'shimmer 1.5s ease-in-out infinite',
    }}
  />
);

const Spinner = ({ className, size = '24px' }) => (
  <div
    className={`spinner ${className || ''}`}
    style={{
      width: size,
      height: size,
      border: '3px solid #f3f3f3',
      borderTop: '3px solid #cc3838',
      borderRadius: '50%',
      animation: 'spin 1s linear infinite',
    }}
  />
);

const skeletonStyles = `
  @keyframes shimmer {
    0% { background-color: #f0f0f0; }
    50% { background-color: #e0e0e0; }
    100% { background-color: #f0f0f0; }
  }
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
`;

export { Skeleton, Spinner, skeletonStyles };