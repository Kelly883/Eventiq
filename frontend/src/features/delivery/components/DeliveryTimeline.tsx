import React from 'react';

/**
 * DeliveryTimeline – Displays a vertical timeline of all delivery attempts
 * with timestamps, status, and error messages.
 *
 * Takes a deliveryHistory array (from the API response) and renders each attempt
 * as a timeline item. Expandable items show full error details.
 *
 * Used on TicketStatusPage.
 */
interface DeliveryAttempt {
  /** Unique identifier for this attempt */
  id: string;
  /** Timestamp of the attempt */
  timestamp: string | Date;
  /** Status of the attempt */
  status: 'pending' | 'sent' | 'failed' | 'viewed' | 'bounced';
  /** Optional error message if the attempt failed */
  error?: string;
  /** Optional human-readable description */
  description?: string;
}

interface DeliveryTimelineItemProps {
  attempt: DeliveryAttempt;
  /** Callback when expanding/collapsing the item */
  onToggleExpanded?: (id: string) => void;
  /** Optional whether this item is currently expanded */
  isExpanded?: boolean;
}

interface DeliveryTimelineProps {
  /** Array of delivery attempts in chronological order */
  deliveryHistory: DeliveryAttempt[];
  /** Optional callback when expanding/collapsing items */
  onItemToggle?: (attemptId: string) => void;
  /** Optional className override */
  className?: string;
}

/** Format a timestamp to a readable string */
const formatTimestamp = (timestamp: string | Date): string => {
  const date = typeof timestamp === 'object' ? timestamp : new Date(timestamp);
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

/** Get the color class for a status */
const getStatusClass = (status: DeliveryAttempt['status']): string => {
  switch (status) {
    case 'sent':
      return 'bg-success-100 text-success-800';
    case 'failed':
      return 'bg-error-100 text-error-800';
    case 'pending':
      return 'bg-warning-100 text-warning-800';
    case 'viewed':
      return 'bg-primary-100 text-primary-800';
    case 'bounced':
      return 'bg-warning-100 text-warning-800';
    default:
      return 'bg-slate-100 text-slate-600';
  }
};

/** Get the label for a status */
const getStatusLabel = (status: DeliveryAttempt['status']): string => {
  switch (status) {
    case 'sent':
      return 'Sent';
    case 'failed':
      return 'Failed';
    case 'pending':
      return 'Pending';
    case 'viewed':
      return 'Viewed';
    case 'bounced':
      return 'Bounced';
    default:
      return 'Unknown';
  }
};

export const DeliveryTimelineItem: React.FC<DeliveryTimelineItemProps> = ({
  attempt,
  onToggleExpanded,
  isExpanded = false,
}) => {
  const [expanded, setExpanded] = React.useState(isExpanded);
  const id = attempt.id;

  return (
    <div className="border-b last:border-0 pb-4">
      <div
        className="
          cursor-pointer
          flex items-start justify-between
          py-3
          last:py-0
          last:border-0
        "
        onClick={() => onToggleExpanded?.(id)}
      >
        <div className="flex items-center gap-3 w-full">
          <div
            className="
              h-8 w-8 rounded-full flex-shrink-0
              flex items-center justify-center
              min-w-8
            "
          >
            <span className="text-xs font-bold">
              {attempt.attemptNumber ?? attempt.id.substring(0, 8)}
            </span>
          </div>

          <div className="flex-1 min-w-0">
            <p className="font-medium text-slate-900 truncate">
              {formatTimestamp(attempt.timestamp)}
            </p>
            <p className="text-slate-500 text-xs truncate">
              {getStatusLabel(attempt.status)}
            </p>
          </div>

          <div className={`w-3 h-3 rounded-full ${getStatusClass(attempt.status)} flex-shrink-0`} />
        </div>

        {/* Expand/collapse arrow */}
        {onToggleExpanded && (
          <svg
            className="w-4 h-4 text-slate-400 transition-transform duration-200"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            style={{
              transform: expanded ? 'rotate(180deg)' : 'rotate(0deg)',
            }}
          >
            <path d="M6 9l6 6 6-6" />
          </svg>
        )}
      </div>

      {/* Expanded error details */}
      {expanded && attempt.error && (
        <div
          className="
            mt-3 p-4 bg-slate-50 rounded-lg text-slate-600 text-sm
            last:mt-0
          "
        >
          <p className="font-medium text-slate-800 mb-1">Error</p>
          <p className="whitespace-pre-line">{attempt.error}</p>
        </div>
      )}
    </div>
  );
};

export const DeliveryTimeline: React.FC<DeliveryTimelineProps> = ({
  deliveryHistory,
  onItemToggle,
  className,
}) => {
  // Sort by timestamp in descending order (newest first) or ascending as received
  const sortedHistory = [...deliveryHistory].sort(
    (a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  );

  return (
    <div className={`space-y-2 ${className}`}>
      {sortedHistory.map((attempt, index) => (
        <DeliveryTimelineItem
          key={attempt.id}
          attempt={{ ...attempt, attemptNumber: index + 1 }}
          onToggleExpanded={onItemToggle}
          isExpanded={false} // Controlled via parent state
        />
      ))}
    </div>
  );
};

export default DeliveryTimeline;
