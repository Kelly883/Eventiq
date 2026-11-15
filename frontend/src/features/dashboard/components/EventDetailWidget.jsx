import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import { X, Share2, Trash2, Calendar, MapPin } from 'lucide-react';
import { formatEventDate, formatEndsIn } from '@/lib/dateUtils';

const TIER_COLORS = {
  'early-bird': 'bg-indigo-50 text-indigo-700 border-indigo-100',
  'standard': 'bg-blue-50 text-blue-700 border-blue-100',
  'vip': 'bg-purple-50 text-purple-700 border-purple-100',
  'premium': 'bg-amber-50 text-amber-700 border-amber-100',
  'late': 'bg-rose-50 text-rose-700 border-rose-100',
};

export const EventDetailWidget = ({
  event,
  onClose,
  onEdit,
  onViewTickets,
  onShare,
  onDelete,
}) => {
  const handleClose = useCallback(() => {
    onClose?.();
  }, [onClose]);

  const handleEdit = useCallback((e) => {
    e.stopPropagation();
    onEdit?.(event);
  }, [onEdit, event]);

  const handleViewTickets = useCallback((e) => {
    e.stopPropagation();
    onViewTickets?.(event);
  }, [onViewTickets, event]);

  const handleShare = useCallback((e) => {
    e.stopPropagation();
    onShare?.(event);
  }, [onShare, event]);

  const handleDelete = useCallback((e) => {
    e.stopPropagation();
    onDelete?.(event);
  }, [onDelete, event]);

  const tiers = event.tiers || [];
  const totalRevenue = tiers.reduce((sum, t) => sum + (t.revenue || 0), 0);
  const totalAttendees = tiers.reduce((sum, t) => sum + (t.sold || 0), 0);
  const totalCapacity = tiers.reduce((sum, t) => sum + (t.capacity || 0), 0);

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
      onClick={handleClose}
      role="dialog"
      aria-modal="true"
      aria-label="Event details"
    >
      <div
        className="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-xl overflow-hidden flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="relative h-64 overflow-hidden">
          <img
            src={event.thumbnail || ''}
            alt={event.title}
            className="w-full h-full object-cover"
            onError={(e) => { e.currentTarget.style.display = 'none'; }}
          />
          <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent" />
          <div className="absolute bottom-4 left-4 right-4">
            <div className="flex items-center justify-between gap-2">
              <span className="px-2.5 py-1 rounded-md text-xs font-semibold border bg-slate-100 text-slate-700 border-slate-200">
                {(event.status ?? 'event').charAt(0).toUpperCase() + (event.status ?? 'event').slice(1)}
              </span>
              <button
                onClick={handleClose}
                className="w-8 h-8 rounded-full bg-white/20 backdrop-blur text-white flex items-center justify-center hover:bg-white/30 transition-colors"
                aria-label="Close"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
            <h1 className="mt-2 text-2xl font-bold text-white">{event.title}</h1>
            <p className="text-slate-200 text-sm">{event.description ?? 'No description available.'}</p>
          </div>
        </div>

        <div className="p-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div className="flex flex-col gap-1">
              <span className="text-xs text-slate-500 dark:text-slate-400">Date</span>
              <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                {formatEventDate(event.date, 'PPPP') || '—'}
              </span>
            </div>
            <div className="flex flex-col gap-1">
              <span className="text-xs text-slate-500 dark:text-slate-400">Location</span>
              <span className="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                {event.location ?? '—'}
              </span>
            </div>
            <div className="flex flex-col gap-1">
              <span className="text-xs text-slate-500 dark:text-slate-400">Status</span>
              <span className={`text-sm font-semibold capitalize ${
                event.status === 'published' ? 'text-green-600 dark:text-green-400' :
                event.status === 'live' ? 'text-indigo-600 dark:text-indigo-400' :
                event.status === 'cancelled' ? 'text-rose-600 dark:text-rose-400' :
                'text-slate-600 dark:text-slate-300'
              }`}>
                {event.status ?? '—'}
              </span>
            </div>
            <div className="flex flex-col gap-1">
              <span className="text-xs text-slate-500 dark:text-slate-400">Sales end</span>
              <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                {formatEndsIn(event.salesEndDate ?? event.date) || '—'}
              </span>
            </div>
          </div>

          <div className="mb-6">
            <h2 className="text-lg font-bold text-slate-900 mb-3">Ticket Tiers</h2>
            {tiers.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="border-b border-slate-100 dark:border-slate-700">
                      <th className="pb-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Tier</th>
                      <th className="pb-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase text-right">Price</th>
                      <th className="pb-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase text-right">Capacity</th>
                      <th className="pb-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase text-right">Sold</th>
                      <th className="pb-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase text-right">Revenue</th>
                    </tr>
                  </thead>
                  <tbody>
                    {tiers.map((tier) => (
                      <tr key={tier.id ?? tier.name} className="border-b border-slate-50 dark:border-slate-800">
                        <td className="py-3">
                          <div className="flex items-center gap-2">
                            <span className={`px-2 py-0.5 rounded text-xs font-medium ${TIER_COLORS[tier.type ?? 'standard'] ?? TIER_COLORS.standard}`}>
                              {tier.name}
                            </span>
                          </div>
                        </td>
                        <td className="py-3 text-right text-sm font-medium text-slate-900 dark:text-slate-100">
                          {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tier.price ?? 0)}
                        </td>
                        <td className="py-3 text-right text-sm text-slate-600 dark:text-slate-300">{tier.capacity ?? '—'}</td>
                        <td className="py-3 text-right text-sm text-slate-600 dark:text-slate-300">{tier.sold ?? 0}</td>
                        <td className="py-3 text-right text-sm font-medium text-slate-900 dark:text-slate-100">
                          {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tier.revenue ?? 0)}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="p-4 rounded-lg bg-slate-50 border border-slate-100 text-center">
                <p className="text-sm text-slate-500">No ticket tiers defined.</p>
              </div>
            )}
          </div>

            <div className="mb-6">
            <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100 mb-3">Pricing Timeline</h2>
            <div className="relative pl-4">
              <div className="absolute left-0 top-2 bottom-2 w-px bg-slate-200 dark:bg-slate-700" />
              {tiers.length > 0 ? tiers.map((tier, idx) => (
                <div key={tier.id ?? tier.name} className="mb-6 relative">
                  <div className="absolute left-0 top-2 w-2 h-2 rounded-full bg-indigo-500 border-2 border-white dark:border-slate-900" />
                  <span className="text-xs font-semibold text-slate-500 dark:text-slate-400">{tier.name}</span>
                  <div className="mt-1 space-y-1">
                    <div className="flex justify-between text-sm">
                      <span className="text-slate-600 dark:text-slate-300">Price</span>
                      <span className="font-medium text-slate-900 dark:text-slate-100">
                        {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tier.price ?? 0)}
                      </span>
                    </div>
                    {tier.startDate && (
                      <div className="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Starts</span>
                        <span>{formatEventDate(tier.startDate, 'MMM d')}</span>
                      </div>
                    )}
                    {tier.endDate && (
                      <div className="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Ends</span>
                        <span>{formatEventDate(tier.endDate, 'MMM d')}</span>
                      </div>
                    )}
                  </div>
                </div>
              )) : (
                <div className="text-center py-4">
                  <p className="text-sm text-slate-500 dark:text-slate-400">No pricing schedule available.</p>
                </div>
              )}
            </div>
          </div>

          <div className="border-t border-slate-100 dark:border-slate-700 pt-6 flex items-center justify-between">
            <div className="flex flex-col gap-1">
              <span className="text-xs text-slate-500 dark:text-slate-400">Total Revenue</span>
              <span className="text-sm font-bold text-slate-900 dark:text-slate-100">
                {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(totalRevenue)}
              </span>
            </div>
            <div className="flex flex-col gap-1 text-right">
              <span className="text-xs text-slate-500 dark:text-slate-400">Total Attendees</span>
              <span className="text-sm font-bold text-slate-900 dark:text-slate-100">
                {totalAttendees} / {totalCapacity || '—'}
              </span>
            </div>
          </div>
        </div>

        <div className="bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 p-4 flex gap-2">
          <button
            onClick={handleEdit}
            className="flex-1 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            Edit Event
          </button>
          <button
            onClick={handleViewTickets}
            className="flex-1 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 dark:bg-indigo-500 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors"
          >
            View Tickets
          </button>
          <button
            onClick={handleShare}
            className="w-10 p-2 text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
            aria-label="Share"
          >
            <Share2 className="w-5 h-5" />
          </button>
          <button
            onClick={handleDelete}
            className="w-10 p-2 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
            aria-label="Delete"
          >
            <Trash2 className="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  );
};

EventDetailWidget.displayName = 'EventDetailWidget';

EventDetailWidget.propTypes = {
  event: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
    title: PropTypes.string.isRequired,
    description: PropTypes.string,
    date: PropTypes.string,
    location: PropTypes.string,
    status: PropTypes.oneOf(['published', 'draft', 'cancelled', 'live']),
    salesEndDate: PropTypes.string,
    thumbnail: PropTypes.string,
    tiers: PropTypes.arrayOf(PropTypes.object),
  }).isRequired,
  onClose: PropTypes.func,
  onEdit: PropTypes.func,
  onViewTickets: PropTypes.func,
  onShare: PropTypes.func,
  onDelete: PropTypes.func,
};

EventDetailWidget.defaultProps = {
  onClose: undefined,
  onEdit: undefined,
  onViewTickets: undefined,
  onShare: undefined,
  onDelete: undefined,
};

export default EventDetailWidget;
