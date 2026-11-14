import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { EventDetailWidget } from './EventDetailWidget'

describe('EventDetailWidget', () => {
  const mockEvent = {
    id: 'event-1',
    title: 'Tech Conference 2024',
    desc: 'A tech conference',
    bannerUrl: '/images/banner.jpg',
    status: 'live',
    startDate: '2024-03-15',
    endDate: '2024-03-17',
    venueName: ' Convention Center',
    revenue: 500000,
    attendeeCount: 250,
    ticketsSold: 250,
    ticketsTotal: 500,
    organizerName: 'John Doe',
    category: 'Conference',
    tiers: [
      { tier: 'Early Bird', sold: 100, capacity: 200, price: 5000 },
      { tier: 'Regular', sold: 100, capacity: 300, price: 8000 },
    ],
    pricing_timeline: [
      { label: 'Early Bird', price: 5000, date: '2024-01-01' },
      { label: 'Regular', price: 8000, date: '2024-02-01' },
    ],
  }

  it('shows event info, tier breakdown, and pricing timeline', () => {
    render(
      <EventDetailWidget
        eventId="event-1"
        onViewAnalytics={() => {}}
        onManageInventory={() => {}}
        onEditEvent={() => {}}
      />
    )

    expect(screen.getByText('Tech Conference 2024')).toBeInTheDocument()
    expect(screen.getByText('A tech conference')).toBeInTheDocument()
    expect(screen.getByText('John Doe')).toBeInTheDocument()
    expect(screen.getByText('Conference')).toBeInTheDocument()
    expect(screen.getByText('Early Bird')).toBeInTheDocument()
    expect(screen.getByText('Regular')).toBeInTheDocument()
    expect(screen.getByText('₦ 5,000')).toBeInTheDocument()
    expect(screen.getByText('₦ 8,000')).toBeInTheDocument()
  })

  it('buttons navigate to the correct pages', () => {
    const onViewAnalytics = jest.fn()
    const onManageInventory = jest.fn()
    const onEditEvent = jest.fn()

    render(
      <EventDetailWidget
        eventId="event-1"
        onViewAnalytics={onViewAnalytics}
        onManageInventory={onManageInventory}
        onEditEvent={onEditEvent}
      />
    )

    fireEvent.click(screen.getByRole('button', { name: /view analytics/i }))
    expect(onViewAnalytics).toHaveBeenCalledTimes(1)

    fireEvent.click(screen.getByRole('button', { name: /manage inventory/i }))
    expect(onManageInventory).toHaveBeenCalledTimes(1)

    fireEvent.click(screen.getByRole('button', { name: /edit event/i }))
    expect(onEditEvent).toHaveBeenCalledTimes(1)
  })
})
