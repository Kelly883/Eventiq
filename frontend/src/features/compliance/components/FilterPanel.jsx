import React from 'react';

const FilterPanel = ({ filters, onFiltersChange }) => {
  const update = (patch) => onFiltersChange?.({ ...(filters || {}), ...patch });

  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="text-sm font-semibold text-gray-900">Filters</div>
      <div className="mt-3 flex gap-3 flex-wrap">
        <input
          className="border rounded px-3 py-2 text-sm"
          placeholder="Search logs..."
          value={filters?.query ?? ''}
          onChange={(e) => update({ query: e.target.value })}
        />
        <select
          className="border rounded px-3 py-2 text-sm"
          value={filters?.action ?? ''}
          onChange={(e) => update({ action: e.target.value })}
        >
          <option value="">All actions</option>
          <option value="user_login">User login</option>
          <option value="user_logout">User logout</option>
          <option value="event_created">Event created</option>
          <option value="event_approved">Event approved</option>
          <option value="event_flagged">Event flagged</option>
          <option value="event_cancelled">Event cancelled</option>
          <option value="payment_processed">Payment processed</option>
          <option value="payment_refunded">Payment refunded</option>
          <option value="refund.requested">Refund requested</option>
          <option value="refund_approved">Refund approved</option>
          <option value="refund_rejected">Refund rejected</option>
          <option value="payout_approved">Payout approved</option>
          <option value="payout_rejected">Payout rejected</option>
          <option value="ticket_checked_in">Ticket checked in</option>
          <option value="ticket_voided">Ticket voided</option>
          <option value="fraud_flagged">Fraud flagged</option>
          <option value="fraud_approved">Fraud approved</option>
          <option value="admin_setting_changed">Admin setting changed</option>
          <option value="user_permission_changed">User permission changed</option>
          <option value="data_export_requested">Data export requested</option>
          <option value="check_in">Check in</option>
        </select>
        <select
          className="border rounded px-3 py-2 text-sm"
          value={filters?.targetType ?? ''}
          onChange={(e) => update({ targetType: e.target.value })}
        >
          <option value="">All entities</option>
          <option value="user">User</option>
          <option value="event">Event</option>
          <option value="order">Order</option>
          <option value="payment">Payment</option>
          <option value="payout">Payout</option>
          <option value="refund">Refund</option>
          <option value="setting">Setting</option>
          <option value="ticket">Ticket</option>
        </select>
        <select
          className="border rounded px-3 py-2 text-sm"
          value={filters?.status ?? ''}
          onChange={(e) => update({ status: e.target.value })}
        >
          <option value="">All statuses</option>
          <option value="success">Success</option>
          <option value="failure">Failure</option>
          <option value="warning">Warning</option>
          <option value="pending">Pending</option>
        </select>
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          value={filters?.start ?? ''}
          onChange={(e) => update({ start: e.target.value })}
        />
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          value={filters?.end ?? ''}
          onChange={(e) => update({ end: e.target.value })}
        />
      </div>
    </div>
  );
};

export { FilterPanel };
