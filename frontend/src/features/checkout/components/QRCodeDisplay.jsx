import React, { useEffect, useState, useRef } from 'react';
import { QRCodeSVG } from 'qrcode.react';

const QRCodeDisplay = ({ data, size = 200, label = 'QR Code', lazy = false }) => {
  const containerRef = useRef(null);
  const [render, setRender] = useState(!lazy);

  useEffect(() => {
    if (!lazy) return;
    const el = containerRef.current;
    if (!el) return;
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          setRender(true);
          observer.disconnect();
        }
      },
      { threshold: 0.1 },
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, [lazy]);

  if (!data) {
    return (
      <div
        ref={containerRef}
        className="h-[200px] w-[200px] flex items-center justify-center text-sm text-gray-500"
        role="img"
        aria-label={label}
      >
        QR code not available
      </div>
    );
  }

  if (!render) {
    return (
      <div
        ref={containerRef}
        className="relative"
        style={{ width: size, height: size }}
        role="img"
        aria-label={label}
      >
        <div className="absolute inset-0 flex items-center justify-center bg-slate-100 rounded-lg border border-slate-200 animate-pulse">
          <span className="text-xs text-slate-400">QR code</span>
        </div>
      </div>
    );
  }

  return (
    <div
      ref={containerRef}
      className="relative"
      style={{ width: size, height: size }}
    >
      <QRCodeSVG
        value={data}
        size={size}
        level="M"
        includeMargin
        aria-label={label}
        title={label}
      />
      {data.length > 0 && (
        <span className="absolute bottom-1 left-1 text-xs text-slate-700 capitalize">
          {label}
        </span>
      )}
    </div>
  );
};

QRCodeDisplay.defaultProps = {
  size: 200,
  label: 'QR Code',
  lazy: false,
};

export default QRCodeDisplay;