import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useEvent } from '../../hooks/useEvents';
import { toast } from '@/lib/toast';

interface BottomSheetProps {
  eventId: number | string;
  onClose?: () => void;
}

const BottomSheet = ({ eventId, onClose }: BottomSheetProps) => {
  const navigate = useNavigate();
  const { event, isLoading, error } = useEvent(eventId as number);
  const [height, setHeight] = useState(0);
  const sheetRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const updateHeight = () => {
      if (sheetRef.current) {
        setHeight(Math.min(sheetRef.current.scrollHeight, 400));
      }
    };

    const handleResize = () => {
      updateHeight();
    };

    window.addEventListener('resize', handleResize);
    updateHeight();
    return () => window.removeEventListener('resize', handleResize);
  }, [eventId]);

  if (isLoading) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div className="bg-white rounded-2xl p-8 animate-fade-in">
          <div className="animate-pulse h-64 w-full rounded-lg mb-6" />
          <p className="text-slate-500">Loading event details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div className="bg-white rounded-2xl p-6 text-center">
          <p className="text-rose-600">Error loading event details</p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  if (!event) {
    return null;
  }

  const handleClose = () => {
    setHeight(0);
    onClose?.();
    // Small delay to allow the slide-up animation
    setTimeout(() => {
      navigate(-1);
    }, 300);
  };

  return (
    <div
      className={`fixed bottom-0 left-0 right-0 z-50 transform transition-transform duration-500 ease-out-sm ${height > 0 ? 'translate-y-0' : 'translate-y-full'}`}
      ref={sheetRef}
    >
      <div
        className={`max-w-full w-full bg-white rounded-2xl overflow-hidden shadow-2xl shadow-slate-200 ${height > 0 ? 'max-h-[400px]' : 'max-h-0'}`}
      >
        <div className="flex items-center justify-between p-4 border-b border-slate-200">
          <h3 className="text-lg font-medium">{event.title}</h3>
          <button
            onClick={handleClose}
            className="text-slate-500 hover:text-slate-700"
            aria-label="Close event details"
          >
            ✕
          </button>
        </div>
        <div className="p-6 space-y-4" style={{ maxHeight: height > 0 ? height : undefined }}>
          <div>
            <p className="text-sm text-gray-500">Date:</p>
            <p className="text-base">{event.date?.split('T')[0] || 'Date TBA'}</p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Venue:</p>
            <p className="text-base">{event.venue?.name || 'Unknown venue'}</p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Time:</p>
            <p className="text-base">
              {event.startTime?.split('T')[1] || 'TBA'} - {event.endTime?.split('T')[1] || 'TBA'}
            </p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Organizer:</p>
            <p className="text-base">{event.organizer?.name || 'Unknown organizer'}</p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Description:</p>
            <p className="text-base line-clamp-3">{event.description || 'No description available'}</p>
          </div>
          {event.ticketTiers && event.ticketTiers.length > 0 && (
            <div>
              <p className="text-sm text-gray-500">Ticket Options:</p>
              <div className="grid grid-cols-2 gap-2">
                {event.ticketTiers.map((tier: any, index: number) => (
                  <div
                    key={index}
                    className="border rounded p-3 hover:bg-slate-50 transition-colors"
                  >
                    <p className="font-medium">{tier.name || 'General'}</p>
                    <p className="text-right">from €{tier.currentPrice?.toFixed(2) || 'Free'}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
        <div className="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
          <button
            onClick={handleClose}
            className="px-6 py-2 bg-indigo-600 text-white rounded text-sm font-medium hover:bg-indigo-700 transition-colors"
            aria-label="Close and go back"
          >
            Go Back
          </button>
        </div>
      </div>
    </div>
  );
};

export default BottomSheet;