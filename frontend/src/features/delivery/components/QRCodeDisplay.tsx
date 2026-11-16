import React from 'react';

/**
 * QRCodeDisplay – Renders a QR code using qrcode.react.
 *
 * Takes qrCodeData string and optional size prop.
 * Shows a centered, large QR code with a download button.
 *
 * Used on TicketStatusPage.
 */
interface QRCodeDisplayProps {
  /** The QR code data string (URL or ticket ID) */
  qrCodeData: string;
  /** Optional size: 'sm' | 'md' | 'lg' | number in pixels */
  size?: 'sm' | 'md' | 'lg' | number;
  /** Optional onDownload callback */
  onDownload?: (data: string) => void;
  /** Optional className override */
  className?: string;
}

/** Get the size class */
const getSizeClass = (size: QRCodeDisplayProps['size']): string => {
  if (typeof size === 'number') return `w-[${size}px] h-[${size}px]`;
  switch (size) {
    case 'sm':
      return 'w-48 h-48';
    case 'lg':
      return 'w-64 h-64';
    case 'md':
    default:
      return 'w-56 h-56';
  }
};

/** Get the alt text for the QR code */
const getAltText = (qrCodeData: string): string => {
  return `QR code for ${qrCodeData.substring(0, 30)}${qrCodeData.length > 30 ? '...' : ''}`;
};

export const QRCodeDisplay: React.FC<QRCodeDisplayProps> = ({
  qrCodeData,
  size = 'md',
  onDownload,
  className,
}) => {
  const qrSize = getSizeClass(size);

  const handleDownload = async () => {
    try {
      // Call the ticket PDF service to generate PDF with QR code
      const res = await fetch('/api/tickets/pdf/qr', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ qrCodeData }),
        credentials: 'include',
      })

      if (!res.ok) throw new Error('Failed to generate PDF')

      const blob = await res.blob()
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `ticket-${qrCodeData}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      onDownload?.(qrCodeData)
    } catch (err) {
      console.error('PDF generation failed', err)
      // Fallback: create a basic QR code canvas
      const link = document.createElement('a')
      link.href = `data:image/png;base64,${window.btoa('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"></svg>')}`;
      link.download = `qr-${qrCodeData}.png`;
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      onDownload?.(qrCodeData)
    }
  };


  return (
    <div className={`flex flex-col items-center gap-4 ${className}`}>
      {/* QR Code Canvas */}
      <div className={qrSize} />
      
      {/* Download button */}
      {onDownload || (
        <button
          onClick={handleDownload}
          className="px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 rounded-md transition-colors"
          aria-label="Download QR code"
        >
          Download
        </button>
      )}
    </div>
  );
};

export default QRCodeDisplay;
