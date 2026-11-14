import React, { useRef, useEffect } from 'react';

export interface PriceRangeSliderProps {
  min: number;
  max: number;
  value: [number, number];
  onChange: (value: [number, number]) => void;
  step?: number;
  currency?: string;
  formatValue?: (value: number) => string;
}

export const PriceRangeSlider = ({
  min,
  max,
  value,
  onChange,
  step = 500,
  currency = 'NGN',
  formatValue,
}: PriceRangeSliderProps) => {
  const trackRef = useRef<HTMLDivElement>(null);
  const thumbMinRef = useRef<HTMLButtonElement>(null);
  const thumbMaxRef = useRef<HTMLButtonElement>(null);
  const isDraggingMin = useRef(false);
  const isDraggingMax = useRef(false);

  const formatCurrency = (val: number) => {
    if (formatValue) return formatValue(val);
    const symbols: Record<string, string> = {
      USD: '$', EUR: '€', GBP: '£', NGN: '₦', GHS: '₵', KES: 'KSh', ZAR: 'R',
    };
    const symbol = symbols[currency.toUpperCase()] || '₦';
    return `${symbol}${val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
  };

  const getPosition = (val: number) => {
    return ((val - min) / (max - min)) * 100;
  };

  const minPos = getPosition(value[0]);
  const maxPos = getPosition(value[1]);

  const updateValue = (newMin: number, newMax: number) => {
    const clampedMin = Math.max(min, Math.min(newMin, newMax - step));
    const clampedMax = Math.min(max, Math.max(newMax, clampedMin + step));
    
    // Round to step
    const roundedMin = Math.round(clampedMin / step) * step;
    const roundedMax = Math.round(clampedMax / step) * step;
    
    onChange([roundedMin, roundedMax]);
  };

  const handleMouseDown = (e: React.MouseEvent, isMin: boolean) => {
    e.preventDefault();
    if (isMin) {
      isDraggingMin.current = true;
    } else {
      isDraggingMax.current = true;
    }
    
    const handleMouseMove = (moveEvent: MouseEvent) => {
      if (!trackRef.current) return;
      
      const rect = trackRef.current.getBoundingClientRect();
      const percentage = Math.max(0, Math.min(1, (moveEvent.clientX - rect.left) / rect.width));
      const newValue = min + percentage * (max - min);
      const steppedValue = Math.round(newValue / step) * step;
      
      if (isDraggingMin.current) {
        updateValue(steppedValue, value[1]);
      } else if (isDraggingMax.current) {
        updateValue(value[0], steppedValue);
      }
    };
    
    const handleMouseUp = () => {
      isDraggingMin.current = false;
      isDraggingMax.current = false;
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
    
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', handleMouseUp);
  };

  const handleKeyDown = (e: React.KeyboardEvent, isMin: boolean) => {
    const currentValue = isMin ? value[0] : value[1];
    const otherValue = isMin ? value[1] : value[0];
    let newValue = currentValue;
    
    switch (e.key) {
      case 'ArrowRight':
      case 'ArrowUp':
        e.preventDefault();
        newValue = Math.min(isMin ? otherValue - step : max, currentValue + step);
        break;
      case 'ArrowLeft':
      case 'ArrowDown':
        e.preventDefault();
        newValue = Math.max(isMin ? min : otherValue + step, currentValue - step);
        break;
      case 'Home':
        e.preventDefault();
        newValue = isMin ? min : otherValue + step;
        break;
      case 'End':
        e.preventDefault();
        newValue = isMin ? otherValue - step : max;
        break;
      default:
        return;
    }
    
    updateValue(isMin ? newValue : otherValue, isMin ? otherValue : newValue);
  };

  return (
    <div className="price-range-slider" role="application" aria-label="Price range">
      <div className="price-range-slider__values">
        <span className="price-range-slider__value price-range-slider__value--min">
          {formatCurrency(value[0])}
        </span>
        <span className="price-range-slider__separator">–</span>
        <span className="price-range-slider__value price-range-slider__value--max">
          {value[1] >= max ? `${formatCurrency(max)}+` : formatCurrency(value[1])}
        </span>
      </div>
      
      <div
        ref={trackRef}
        className="price-range-slider__track"
        onClick={(e) => {
          if (!trackRef.current) return;
          const rect = trackRef.current.getBoundingClientRect();
          const percentage = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
          const newValue = min + percentage * (max - min);
          const steppedValue = Math.round(newValue / step) * step;
          
          // Determine which thumb is closer
          const minDist = Math.abs(steppedValue - value[0]);
          const maxDist = Math.abs(steppedValue - value[1]);
          
          if (minDist < maxDist) {
            updateValue(steppedValue, value[1]);
          } else {
            updateValue(value[0], steppedValue);
          }
        }}
      >
        <div
          className="price-range-slider__range"
          style={{ left: `${minPos}%`, right: `${100 - maxPos}%` }}
        />
        <button
          ref={thumbMinRef}
          type="button"
          className="price-range-slider__thumb price-range-slider__thumb--min"
          style={{ left: `${minPos}%` }}
          onMouseDown={(e) => handleMouseDown(e, true)}
          onKeyDown={(e) => handleKeyDown(e, true)}
          aria-label={`Minimum price: ${formatCurrency(value[0])}`}
          aria-valuemin={min}
          aria-valuemax={value[1] - step}
          aria-valuenow={value[0]}
          role="slider"
          tabIndex={0}
        />
        <button
          ref={thumbMaxRef}
          type="button"
          className="price-range-slider__thumb price-range-slider__thumb--max"
          style={{ left: `${maxPos}%` }}
          onMouseDown={(e) => handleMouseDown(e, false)}
          onKeyDown={(e) => handleKeyDown(e, false)}
          aria-label={`Maximum price: ${value[1] >= max ? `${formatCurrency(max)}+` : formatCurrency(value[1])}`}
          aria-valuemin={value[0] + step}
          aria-valuemax={max}
          aria-valuenow={value[1]}
          role="slider"
          tabIndex={0}
        />
      </div>
    </div>
  );
};

export default PriceRangeSlider;