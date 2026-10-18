import React from 'react';
import { Event, Availability } from '../../types';

const TRUNCATE_LINES = 2;
const LINE_HEIGHT = 1.2;

const getTruncatedTitle = (title: string, maxLines = TRUNCATE_LINES): string => {
  if (title.length <= 40) return title;
  const trimmed = title.slice(0, 40);
  return trimmed + '...';
};

const AvailabilityIndicator = ({
  availability,
  ticketsRemaining,
}: {
  availability: Availability;
  ticketsRemaining?: number;
}) => {
  const colorMap: Record<Availability, string> = {
    available: '#10b981',
    low: '#f59e0b',
    sold_out: '#ef4444',
  };

  const textMap: Record<Availability, string> = {
    available: `${ticketsRemaining ?? '?' } tickets remaining`,
    low: `${ticketsRemaining ?? 'few'} tickets remaining`,
    sold_out: 'Sold Out',
  };

  const color = colorMap[availability];
  const text = textMap[availability];

  return (
    <span
      className="inline-flex items-center gap-1.5 text-xs font-medium"
      aria-label={text}
    >
      <span
        className="inline-block h-2 w-2 rounded-full shrink-0"
        style={{ backgroundColor: color }}
        aria-hidden="true"
      />
      <span>{text}</span>
    </span>
  );
};

const EventCard = ({ event, onClick }: { event: Event; onClick: () => void }) => {
  const {
    id,
    title,
    organizer,
    startTime,
    endTime,
    venue,
    bannerUrl,
    ticketTiers,
    category,
  } = event;

  const organizerName = organizer.name;
  const organizerColors = organizer.brandingColors;
  const primaryColor = organizerColors?.primary ?? '#3b82f6';

  const ticketsAvailable = ticketTiers?.reduce(
    (sum, tier) => sum + (tier.ticketsAvailable ?? 0),
    0
  ) ?? 0;

  const totalTickets = ticketTiers?.reduce(
    (sum, tier) => sum + (tier.totalTickets ?? 0),
    0
  ) ?? 0;

  const availability =
    totalTickets > 0
      ? ticketsAvailable <= 0
        ? 'sold_out'
        : ticketsAvailable / totalTickets <= 0.2
        ? 'low'
        : 'available'
      : 'sold_out';

  const ticketsRemaining = ticketsAvailable;

  const price = ticketTiers?.[0]?.currentPrice ?? 0;

  const [isMouseOver, setIsMouseOver] = React.useState(false);

  return (
    <div
      className="group rounded-lg overflow-cursor-pointer transition-all duration-200 hover:shadow-md hover:shadow-gray-200/20"
      onClick={onClick}
      role="button"
      tabIndex={0}
      aria-label={`${title} event, ${organizerName}, ${startTime} to ${endTime}`}
      onMouseOver={() => setIsMouseOver(true)}
      onMouseOut={() => setIsMouseOver(false)}
    >
      <div className="relative h-48">
        <img
          src={bannerUrl || '/placeholder-event.jpg'}
          alt={title}
          className="absolute inset-0 w-full h-full object-cover transition-opacity group-hover:opacity-80"
        />
        <div
          className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 via-black/40 to-transparent p-3"
        />
      </div>

      <div className="absolute bottom-0 left-0 right-0 p-3">
        <h3
          className={`line-clamp-2 text-white text-sm font-medium ${isMouseOver ? 'text-primary-400' : 'text-white'}`}
          style={{ lineHeight: `${LINE_HEIGHT}em` }}
        >
          {getTruncatedTitle(title)}
        </h3>
        <p className="text-xs text-white/80 mt-1">
          {organizerName}
        </p>
      </div>

      <div className="absolute inset-0 flex items-end p-3">
        <AvailabilityIndicator
          availability={availability}
          ticketsRemaining={ticketsRemaining}
        />
      </div>

      <div className="absolute bottom-3 left-3 right-3 px-3">
        <p className="text-xs text-white/70">
          {venue?.name || 'Unknown venue'}
        </p>
      </div>

      <div className="absolute bottom-3 left-3">
        <p className="text-xs text-white/70">
          from {startTime} to {endTime}
        </p>
      </div>

      <div className="absolute bottom-3 right-3">
        <p className="text-xs text-white/70">
          from €{price.toFixed(2)}
        </p>
      </div>
    </div>
  );
};

export default EventCard;