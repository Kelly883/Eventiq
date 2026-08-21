import type { OfflineTicket } from '../types';

interface StalenessResult {
  readonly keep: OfflineTicket[];
  readonly invalidate: string[];
}

export function computeStaleTickets(
  incoming: OfflineTicket[],
  cached: OfflineTicket[]
): StalenessResult {
  const cachedByEventId = new Map<string, OfflineTicket[]>();
  const cachedByTierId = new Map<string, OfflineTicket[]>();

  for (const ticket of cached) {
    const eventKey = ticket.eventId;
    const tierKey = ticket.ticketTierId;

    if (!cachedByEventId.has(eventKey)) {
      cachedByEventId.set(eventKey, []);
    }
    cachedByEventId.get(eventKey)!.push(ticket);

    if (!cachedByTierId.has(tierKey)) {
      cachedByTierId.set(tierKey, []);
    }
    cachedByTierId.get(tierKey)!.push(ticket);
  }

  const incomingEventTimestamps = new Map<string, string>();
  const incomingTierTimestamps = new Map<string, string>();

  for (const ticket of incoming) {
    if (ticket.eventUpdatedAt) {
      const existing = incomingEventTimestamps.get(ticket.eventId);
      if (!existing || ticket.eventUpdatedAt > existing) {
        incomingEventTimestamps.set(ticket.eventId, ticket.eventUpdatedAt);
      }
    }
    if (ticket.tierUpdatedAt) {
      const existing = incomingTierTimestamps.get(ticket.ticketTierId);
      if (!existing || ticket.tierUpdatedAt > existing) {
        incomingTierTimestamps.set(ticket.ticketTierId, ticket.tierUpdatedAt);
      }
    }
  }

  const invalidate = new Set<string>();

  for (const [eventId, cachedTickets] of cachedByEventId) {
    const incomingUpdatedAt = incomingEventTimestamps.get(eventId);
    const cachedUpdatedAt = cachedTickets[0]?.eventUpdatedAt ?? null;

    if (incomingUpdatedAt && cachedUpdatedAt && incomingUpdatedAt > cachedUpdatedAt) {
      for (const ticket of cachedTickets) {
        invalidate.add(ticket.id);
      }
    }
  }

  for (const [tierId, cachedTickets] of cachedByTierId) {
    const incomingUpdatedAt = incomingTierTimestamps.get(tierId);
    const cachedUpdatedAt = cachedTickets[0]?.tierUpdatedAt ?? null;

    if (incomingUpdatedAt && cachedUpdatedAt && incomingUpdatedAt > cachedUpdatedAt) {
      for (const ticket of cachedTickets) {
        invalidate.add(ticket.id);
      }
    }
  }

  const keep = incoming.filter((ticket) => !invalidate.has(ticket.id));

  return {
    keep,
    invalidate: Array.from(invalidate),
  };
}
