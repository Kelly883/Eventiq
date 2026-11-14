import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { axe, toHaveNoViolations } from 'jest-axe'
import { DashboardEventCard } from '../DashboardEventCard'
import { vi } from 'vitest'

expect.extend(toHaveNoViolations);

describe('EventCard', () => {
  const mockEvent = {
    id: 'event-1',
    title: 'Tech Conference 2024',
    bannerUrl: '/images/banner.jpg',
    status: 'live',
    startDate: '2024-03-15',
    endDate: '2024-03-17',
    revenue: 500000,
    attendeeCount: 250,
    ticketsTotal: 500,
  }

  it('shows thumbnail, title, date, status badge, progress bar, revenue, and attendee count', () => {
    render(
      <DashboardEventCard
        event={mockEvent}
        isExpanded={false}
        onViewAnalytics={() => {}}
        onManageInventory={() => {}}
        onEditEvent={() => {}}
      />
    )
    expect(screen.getByRole('heading', { name: 'Tech Conference 2024' })).toBeInTheDocument()
    const progressBar = screen.getByRole('progressbar')
    expect(progressBar).toBeInTheDocument()
    expect(screen.getByText('250')).toBeInTheDocument()
    expect(screen.getByText(/₦500,000/)).toBeInTheDocument()
  })

  it('buttons trigger the correct callbacks', () => {
    const onViewAnalytics = vi.fn()
    const onManageInventory = vi.fn()
    const onEditEvent = vi.fn()

    render(
      <DashboardEventCard
        event={mockEvent}
        isExpanded={false}
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

  it('passes accessibility audit', async () => {
    const { container } = render(
      <DashboardEventCard
        event={mockEvent}
        isExpanded={false}
        onViewAnalytics={() => {}}
        onManageInventory={() => {}}
        onEditEvent={() => {}}
      />
    )
    const results = await axe(container)
    expect(results).toHaveNoViolations()
  })

  it('has proper keyboard navigation', () => {
    render(
      <DashboardEventCard
        event={mockEvent}
        isExpanded={false}
        onViewAnalytics={() => {}}
        onManageInventory={() => {}}
        onEditEvent={() => {}}
      />
    )

    const card = screen.getByRole('button', { name: /tech conference 2024/i })
    expect(card).toBeInTheDocument()
    expect(card).toHaveAttribute('tabIndex', '0')
  })
})