import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import { Calendar, X } from 'lucide-react';
import { formatEventDate } from '@/lib/dateUtils';

const STATUS_CONFIG = {
  published: { label: 'Published', color: 'bg-green-100 text-green-700 border-green-200', dot: 'bg-green-500' },
  draft: { label: 'Draft', color: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' },
  cancelled: { label: 'Cancelled', color: 'bg-rose-100 text-rose-700 border-rose-200', dot: 'bg-rose-500' },
  live: { label: 'Live', color: 'bg-indigo-100 text-indigo-700 border-indigo-200', dot: 'bg-indigo-500' },
};

const DEFAULT_THUMBNAIL = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIyNSIgdmlld0JveD0iMCAwIDQwMCAyMjUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMjI1IiBmaWxsPSIjZzNmNGY1Ii8+CjxwYXRoIGQ9Ik0xNjAgMTkwQzE2MCAxOTAuODI4IDE1MC44MjggMjAwIDE0MCAyMDBDMTMzLjE3MiAyMDAgMTI0IDE5MC44MjggMTI0IDE4QzEyNCAxNjkuMTcyIDEzMy4xNzIgMTYwIDE0MCAxNjBDMTUwLjgyOCAxNjAgMTYwIDE2OS4xNzIgMTYwIDE4MFoiIGZpbGw9IiM5QTk5QTkiLz4KPC9zdmc+';

export const EventCard = ({
  event,
  onSelect,
  onEdit,
  onShare,
  onDelete,
  isExpanded = false,
}) => {
  const handleSelect = useCallback(() => {
    onSelect?.(event);
  }, [onSelect, event]);

  const handleEdit = useCallback((e) => {
    e.stopPropagation();
    onEdit?.(event);
  }, [onEdit, event]);

  const handleShare = useCallback((e) => {
    e.stopPropagation();
    onShare?.(event);
  }, [onShare, event]);

  const handleDelete = useCallback((e) => {
    e.stopPropagation();
    onDelete?.(event);
  }, [onDelete, event]);

  const status = STATUS_CONFIG[event.status] || STATUS_CONFIG.draft;
  const progress = event.capacity && event.capacity > 0
    ? Math.min(100, Math.round((event.attendees / event.capacity) * 100))
    : 0;

  return (
    <article
      className={`group relative border rounded-xl overflow-hidden transition-all duration-200 ${
        isExpanded
          ? 'border-indigo-200 dark:border-indigo-900 bg-indigo-50 dark:bg-indigo-950/20 shadow-sm ring-1 ring-indigo-200 dark:ring-indigo-900'
          : 'border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-slate-200 dark:hover:border-slate-600 hover:shadow-md'
      }`}
    >
      <div className="relative h-48 overflow-hidden">
        <img
          src={event.thumbnail || DEFAULT_THUMBNAIL}
          alt={event.title}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          loading="lazy"
          onError={(e) => {
            e.currentTarget.src = DEFAULT_THUMBNAIL;
          }}
        />
        <span className={`absolute top-3 left-3 px-2.5 py-1 rounded-md text-xs font-semibold border ${status.color} flex items-center gap-1.5`}>
          <span className="h-2 w-2 rounded-full {status.dot}" />
          {status.label}
        </span>
      </div>

      <div className="p-4">
        <div className="mb-2 flex items-center justify-between gap-2">
          <h3 className="font-semibold text-slate-900 dark:text-slate-100 line-clamp-2 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
            {event.title}
          </h3>
          {isExpanded ? (
            <button
              onClick={handleSelect}
              className="w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-100 transition-colors"
              aria-label="Collapse"
            >
              <X className="w-3.5 h-3.5" />
            </button>
          ) : null}
        </div>

        <div className="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-3">
          <Calendar className="w-3.5 h-3.5" />
          <span>{formatEventDate(event.date) || '—'}</span>
          {event.location && (
            <>
              <span className="mx-1">•</span>
              <span>{event.location}</span>
            </>
          )}
        </div>

        <div className="mb-3">
          <div className="flex items-baseline justify-between mb-1 text-xs text-slate-500 dark:text-slate-400">
            <span>Attendance</span>
            <span>{event.attendees} / {event.capacity || '—'}</span>
          </div>
          <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
            <div
              className={`h-full transition-all duration-500 ${progress < 30 ? 'bg-rose-500' : progress < 70 ? 'bg-amber-500' : 'bg-green-500'}`}
              style={{ width: `${progress}%` }}
            />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-3 mb-4">
          <div className="flex flex-col gap-0.5">
            <span className="text-xs text-slate-500 dark:text-slate-400">Revenue</span>
            <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">
              {event.revenue !== undefined ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(event.revenue) : '—'}
            </span>
          </div>
          <div className="flex flex-col gap-0.5 justify-end">
            <span className="text-xs text-slate-500 dark:text-slate-400 text-right">Attendees</span>
            <span className="text-sm font-semibold text-slate-900 dark:text-slate-100 text-right">{event.attendees}</span>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-2">
          <button
            onClick={handleEdit}
            className="px-2.5 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            disabled={isExpanded}
          >
            Edit
          </button>
          <button
            onClick={handleShare}
            className="px-2.5 py-1.5 text-xs font-medium text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            disabled={isExpanded}
          >
            Share
          </button>
          <button
            onClick={handleDelete}
            className="px-2.5 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
            disabled={isExpanded}
          >
            Delete
          </button>
        </div>
      </div>
    </article>
  );
};

EventCard.displayName = 'EventCard';

EventCard.propTypes = {
  event: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    title: PropTypes.string.isRequired,
    date: PropTypes.string,
    location: PropTypes.string,
    status: PropTypes.oneOf(['published', 'draft', 'cancelled', 'live']),
    attendees: PropTypes.number,
    capacity: PropTypes.number,
    revenue: PropTypes.number,
    thumbnail: PropTypes.string,
    tiers: PropTypes.arrayOf(PropTypes.object),
  }).isRequired,
  onSelect: PropTypes.func,
  onEdit: PropTypes.func,
  onShare: PropTypes.func,
  onDelete: PropTypes.func,
  isExpanded: PropTypes.bool,
};

EventCard.defaultProps = {
  onSelect: undefined,
  onEdit: undefined,
  onShare: undefined,
  onDelete: undefined,
  isExpanded: false,
};

export default EventCard;
