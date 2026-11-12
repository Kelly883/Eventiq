import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { axe, toHaveNoViolations } from 'jest-axe'
import EventCard from '../EventCard'
import { vi } from 'vitest'

expect.extend(toHaveNoViolations);

vi.mock('../../../lib/api', () => {
  const mockPost = vi.fn().mockResolvedValue({});
  const mockDelete = vi.fn().mockResolvedValue({});
  return {
    api: {
      post: mockPost,
      delete: mockDelete,
      get: vi.fn().mockResolvedValue({ data: {} }),
      put: vi.fn().mockResolvedValue({}),
      patch: vi.fn().mockResolvedValue({}),
    },
  };
});

const mockEvent = {
  id: 1,
  title: 'Lagos Jazz Night',
  category: 'Music',
  start_datetime: '2026-12-01T19:00:00Z',
  venue_name: 'Eko Convention Centre',
  organizer: { name: 'Amaka Okonkwo' },
  ticket_price: 5000,
  currency: 'NGN',
  tickets_remaining: 160,
  capacity: 200,
  banner_image_url: 'https://example.com/image.jpg',
}

describe('EventCard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders event image, title, organizer, date, venue, price and availability badge', () => {
    render(<EventCard event={mockEvent} />)

    expect(screen.getByAltText('Lagos Jazz Night')).toBeInTheDocument()
    expect(screen.getByText('Lagos Jazz Night')).toBeInTheDocument()
    expect(screen.getByText('Amaka Okonkwo')).toBeInTheDocument()
    expect(screen.getByText('Eko Convention Centre')).toBeInTheDocument()
    expect(screen.getByText('₦5,000')).toBeInTheDocument()
    expect(screen.getByText('160 tickets left')).toBeInTheDocument()
  })

  it('View Event button triggers the onClick callback', () => {
    const onClick = vi.fn()
    render(<EventCard event={mockEvent} onClick={onClick} />)

    fireEvent.click(screen.getByRole('button', { name: /view details for lagos jazz night/i }))
    expect(onClick).toHaveBeenCalledTimes(1)
    expect(onClick).toHaveBeenCalledWith(mockEvent)
  })

  it('favorite button toggles favorites without opening the event', () => {
    const onClick = vi.fn()
    const onFavoriteToggle = vi.fn()
    render(<EventCard event={mockEvent} onClick={onClick} onFavoriteToggle={onFavoriteToggle} />)

    const favoriteBtn = screen.getByRole('button', { name: /add to favorites/i })
    fireEvent.click(favoriteBtn)
    expect(onFavoriteToggle).toHaveBeenCalledWith(1)
    expect(onClick).not.toHaveBeenCalled()
  })

  it('shows a skeleton while loading', () => {
    render(<EventCard event={mockEvent} loading={true} />)

    const skeleton = screen.getByRole('article', { hidden: true })
    expect(skeleton).toBeInTheDocument()
    expect(skeleton).toHaveAttribute('aria-hidden', 'true')
  })

  it('handles edge cases: long title, missing image, no organizer, sold out', () => {
    const longTitleEvent = {
      ...mockEvent,
      id: 2,
      title: 'Extraordinarily Long Annual Event Name That Should Truncate Properly',
    }
    render(<EventCard event={longTitleEvent} />)
    expect(screen.getByText('Extraordinarily Long Annual Event Name That Should Truncate Properly')).toBeInTheDocument()

    const noImageEvent = { ...mockEvent, id: 3, banner_image_url: undefined }
    render(<EventCard event={noImageEvent} />)
    const img = screen.getByAltText('Lagos Jazz Night')
    expect(img).toHaveAttribute('src', expect.stringContaining('unsplash'))

    const noOrganizerEvent = { ...mockEvent, id: 4, organizer: undefined }
    render(<EventCard event={noOrganizerEvent} />)
    expect(screen.getByText('Unknown Organizer')).toBeInTheDocument()

    const soldOutEvent = { ...mockEvent, id: 5, tickets_remaining: 0, capacity: 200 }
    render(<EventCard event={soldOutEvent} />)
    expect(screen.getByText('Sold Out')).toBeInTheDocument()
  })

  it('passes accessibility audit', async () => {
    const { container } = render(<EventCard event={mockEvent} onClick={vi.fn()} />)
    const results = await axe(container)
    expect(results).toHaveNoViolations()
  })

  it('has proper keyboard navigation and ARIA attributes', () => {
    render(<EventCard event={mockEvent} onClick={vi.fn()} />)

    const viewEventBtn = screen.getByRole('button', { name: /view details for lagos jazz night/i })
    expect(viewEventBtn).toBeInTheDocument()

    const favoriteBtn = screen.getByRole('button', { name: /add to favorites/i })
    expect(favoriteBtn).toBeInTheDocument()
    expect(favoriteBtn).toHaveAttribute('aria-label', 'Add to favorites')
  })

  it('activates on Enter and Space key presses', () => {
    const onClick = jest.fn()
    render(<EventCard event={mockEvent} onClick={onClick} />)

    const card = screen.getByRole('button', { name: /lagos jazz night event by amaka okonkwo/i })

    fireEvent.keyDown(card, { key: 'Enter' })
    expect(onClick).toHaveBeenCalledTimes(1)

    fireEvent.keyDown(card, { key: ' ' })
    expect(onClick).toHaveBeenCalledTimes(2)
  })

  it('has tooltips for truncated text', () => {
    render(<EventCard event={mockEvent} />)

    const title = screen.getByText('Lagos Jazz Night')
    expect(title).toHaveAttribute('title', 'Lagos Jazz Night')

    const venue = screen.getByText('Eko Convention Centre')
    expect(venue).toHaveAttribute('title', 'Eko Convention Centre')
  })
})