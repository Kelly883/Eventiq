import React from 'react';
import { QRCodeSVG } from 'qrcode.react';

const QRCodeDisplay = ({ data, size = 200, label = 'QR Code' }) => {
  if (!data) {
    return (
      <div className="h-[200px] w-[200px] flex items-center justify-center text-sm text-gray-500" role="img" aria-label={label}>
        QR code not available
      </div>
    );
  }

  return (
    <div className="relative" role="img" aria-label={label}>
      <QRCodeSVG value={data} size={size} level="M" includeMargin />
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
};

export default QRCodeDisplay;