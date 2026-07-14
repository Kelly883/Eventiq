import React, { useState, useEffect, useRef } from 'react';

/**
 * Reusable wrapper that uses IntersectionObserver to lazily mount and render
 * heavy visual elements (like Recharts) only when they are visible in the viewport.
 */
export const LazyChart = ({ children, height = 300, placeholder }) => {
  const [isIntersected, setIsIntersected] = useState(false);
  const containerRef = useRef(null);

  useEffect(() => {
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
      setIsIntersected(true);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsIntersected(true);
          observer.disconnect(); // Trigger once and keep mounted
        }
      },
      {
        rootMargin: '100px', // Pre-trigger 100px before reaching viewport for a smooth user experience
        threshold: 0.01,
      }
    );

    if (containerRef.current) {
      observer.observe(containerRef.current);
    }

    return () => observer.disconnect();
  }, []);

  return (
    <div ref={containerRef} style={{ minHeight: height }} className="w-full">
      {isIntersected ? (
        children
      ) : (
        placeholder || (
          <div 
            style={{ height }} 
            className="flex w-full items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-slate-400 text-xs font-medium animate-pulse"
          >
            Loading chart viewport...
          </div>
        )
      )}
    </div>
  );
};

export default LazyChart;
