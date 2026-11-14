import React from 'react';
import { Skeleton } from './Skeletons';

const skeletonBase = 'bg-slate-200 animate-pulse rounded';

export const EventCardSkeleton = () => (
  <article className="event-card skeleton-card" aria-hidden="true">
    <div className="event-image-container">
      <div className={`${skeletonBase} skeleton-image-lg`} />
      <div className={`${skeletonBase} h-6 w-20`} />
      <div className={`${skeletonBase} h-6 w-16`} />
    </div>
    <div className="event-details">
      <div className={`${skeletonBase} skeleton-title-lg w-3/4`} />
      <div className="event-meta">
        <div className="meta-row">
          <div className={`${skeletonBase} h-4 w-6`} />
          <div className={`${skeletonBase} h-4 w-2/3`} />
        </div>
        <div className="meta-row">
          <div className={`${skeletonBase} h-4 w-6`} />
          <div className={`${skeletonBase} h-4 w-1/2`} />
        </div>
        <div className="event-progress">
          <div className={`${skeletonBase} h-2 w-full`} />
        </div>
      </div>
      <div className="event-footer">
        <div className="event-price">
          <div className={`${skeletonBase} h-3 w-12`} />
          <div className={`${skeletonBase} h-5 w-20`} />
        </div>
        <div className={`${skeletonBase} h-5 w-24`} />
      </div>
    </div>
    <div className={`${skeletonBase} h-10 w-full`} />
  </article>
);

export const EventGridSkeleton = ({ count = 6, columns = 3 }) => (
  <div className="event-grid" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }} aria-label="Loading events">
    {Array.from({ length: count }).map((_, i) => (
      <EventCardSkeleton key={i} />
    ))}
  </div>
);

export const FilterSidebarSkeleton = () => (
  <aside className="filter-sidebar" aria-hidden="true">
    <div className="filter-sidebar__header">
      <div className={`${skeletonBase} h-6 w-24`} />
    </div>
    <div className="filter-sidebar__content">
      {['Category', 'Date Range', 'Price', 'Location'].map((section, i) => (
        <div key={i} className="filter-sidebar__section">
          <div className={`${skeletonBase} h-4 w-1/4 mb-2`} />
          <div className="space-y-2">
            {Array.from({ length: 3 }).map((_, j) => (
              <div key={j} className={`${skeletonBase} h-8 w-full`} />
            ))}
          </div>
        </div>
      ))}
    </div>
  </aside>
);

export const SearchBarSkeleton = () => (
  <div className="search-bar" aria-hidden="true">
    <div className="search-bar__input-wrapper">
      <div className={`${skeletonBase} h-10 w-full`} />
    </div>
  </div>
);

export const TicketTierCardSkeleton = () => (
  <div className="ticket-tier-card" aria-hidden="true">
    <div className="ticket-tier-card__info">
      <div className={`${skeletonBase} h-5 w-2/3`} />
      <div className={`${skeletonBase} h-6 w-20`} />
    </div>
    <div className="ticket-tier-card__availability">
      <div className={`${skeletonBase} h-5 w-24`} />
    </div>
    <div className="ticket-tier-card__select">
      <div className={`${skeletonBase} h-10 w-full`} />
    </div>
  </div>
);

export const OrganizerCardSkeleton = () => (
  <div className="organizer-card" aria-hidden="true">
    <div className={`${skeletonBase} h-14 w-14 rounded-full`} />
    <div className="organizer-card__info">
      <div className={`${skeletonBase} h-5 w-1/3`} />
      <div className={`${skeletonBase} h-4 w-1/2`} />
    </div>
    <div className={`${skeletonBase} h-8 w-20 rounded`} />
  </div>
);

export const PricingWindowSkeleton = () => (
  <div className="pricing-window" aria-hidden="true">
    <div className="pricing-window__current">
      <div className={`${skeletonBase} h-3 w-24`} />
      <div className={`${skeletonBase} h-6 w-28`} />
    </div>
    <div className="pricing-window__next">
      <div className={`${skeletonBase} h-3 w-24`} />
      <div className={`${skeletonBase} h-6 w-28`} />
    </div>
    <div className="pricing-window__countdown">
      <div className={`${skeletonBase} h-4 w-32`} />
    </div>
  </div>
);

export const EventListSkeleton = ({ count = 5 }) => (
  <div className="space-y-3" aria-hidden="true">
    {Array.from({ length: count }).map((_, i) => (
      <div key={i} className="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-xl shadow-sm">
        <div className="flex items-center gap-3 flex-1">
          <div className={`${skeletonBase} h-8 w-8 rounded-lg`} />
          <div className="space-y-1.5 flex-1">
            <div className={`${skeletonBase} h-3.5 w-1/4`} />
            <div className={`${skeletonBase} h-2.5 w-1/6`} />
          </div>
        </div>
        <div className={`${skeletonBase} h-6 w-16 rounded`} />
      </div>
    ))}
  </div>
);

export const DashboardStatCardSkeleton = () => (
  <div className="bg-white border border-slate-100 rounded-xl p-6" aria-hidden="true">
    <div className={`${skeletonBase} h-4 w-1/3 mb-2`} />
    <div className={`${skeletonBase} h-8 w-1/2`} />
    <div className={`${skeletonBase} h-3 w-1/4 mt-2`} />
  </div>
);

export const TableRowSkeleton = ({ columns = 5 }) => (
  <tr aria-hidden="true">
    {Array.from({ length: columns }).map((_, i) => (
      <td key={i} className="px-4 py-3">
        <div className={`${skeletonBase} h-4 w-full`} />
      </td>
    ))}
  </tr>
);

export const TableSkeleton = ({ rows = 5, columns = 5 }) => (
  <table className="w-full" aria-hidden="true">
    <thead>
      <tr>
        {Array.from({ length: columns }).map((_, i) => (
          <th key={i} className="px-4 py-3 text-left">
            <div className={`${skeletonBase} h-4 w-1/2`} />
          </th>
        ))}
      </tr>
    </thead>
    <tbody>
      {Array.from({ length: rows }).map((_, i) => (
        <TableRowSkeleton key={i} columns={columns} />
      ))}
    </tbody>
  </table>
);

export const ModalSkeleton = () => (
  <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" aria-hidden="true">
    <div className="bg-white rounded-xl p-6 max-w-md w-full">
      <div className={`${skeletonBase} h-6 w-1/3 mb-4`} />
      <div className="space-y-3">
        <div className={`${skeletonBase} h-4 w-full`} />
        <div className={`${skeletonBase} h-4 w-5/6`} />
        <div className={`${skeletonBase} h-4 w-2/3`} />
      </div>
      <div className="flex justify-end gap-3 mt-6">
        <div className={`${skeletonBase} h-10 w-20`} />
        <div className={`${skeletonBase} h-10 w-20`} />
      </div>
    </div>
  </div>
);

export const PageSkeleton = ({ hasSidebar = false }) => (
  <div className={`${hasSidebar ? 'flex' : ''} min-h-screen`}>
    {hasSidebar && (
      <aside className="w-64 border-r border-slate-100 p-4 hidden lg:block" aria-hidden="true">
        <div className={`${skeletonBase} h-8 w-1/2 mb-6`} />
        <nav className="space-y-2">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className={`${skeletonBase} h-10 w-full rounded`} />
          ))}
        </nav>
      </aside>
    )}
    <main className="flex-1 p-6" aria-hidden="true">
      <div className={`${skeletonBase} h-8 w-1/4 mb-6`} />
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {Array.from({ length: 6 }).map((_, i) => (
          <EventCardSkeleton key={i} />
        ))}
      </div>
    </main>
  </div>
);