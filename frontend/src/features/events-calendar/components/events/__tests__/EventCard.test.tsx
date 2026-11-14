import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { axe, toHaveNoViolations } from 'jest-axe'
import EventCard from '../EventCard'

expect.extend(toHaveNoViolations);

const mockEvent = {
  id: '1',
  title: 'Lagos Jazz Night',
  organizer: { name: 'Amaka Okonkwo' },
  startTime: '19:00:00',
  endTime: '23:00:00',
  venue: { name: 'Eko Convention Centre' },
  bannerUrl: 'https://example.com/image.jpg',
  ticketTiers: [
    { ticketsAvailable: 160, totalTickets: 200, currentPrice: 50 },
  ],
}

describe('EventCard (Calendar)', () => {
  it('renders event image, title, organizer, date, price, and availability', () => {
    render(<EventCard event={mockEvent} onClick={() => {}} />)

    expect(screen.getByAltText('Lagos Jazz Night')).toBeInTheDocument()
    expect(screen.getByText('Lagos Jazz Night')).toBeInTheDocument()
    expect(screen.getByText('Amaka Okonkwo')).toBeInTheDocument()
    expect(screen.getByText('7:00 PM to 11:00 PM')).toBeInTheDocument()
    expect(screen.getByText('Eko Convention Centre')).toBeInTheDocument()
    expect(screen.getByText('from €50.00')).toBeInTheDocument()
  })

  it('is clickable and triggers the onClick callback', () => {
    const onClick = jest.fn()
    render(<EventCard event={mockEvent} onClick={onClick} />)

    fireEvent.click(screen.getByRole('button', { name: /lagos jazz night event by amaka okonkwo/i }))
    expect(onClick).toHaveBeenCalledTimes(1)
  })

  it('is keyboard operable (Enter and Space activate the card)', () => {
    const onClick = jest.fn()
    render(<EventCard event={mockEvent} onClick={onClick} />)

    const card = screen.getByRole('button', { name: /lagos jazz night event by amaka okonkwo/i })
    fireEvent.keyDown(card, { key: 'Enter' })
    expect(onClick).toHaveBeenCalledTimes(1)

    fireEvent.keyDown(card, { key: ' ' })
    expect(onClick).toHaveBeenCalledTimes(2)
  })

  it('shows availability indicator based on tickets', () => {
    const soldOutEvent = {
      ...mockEvent,
      ticketTiers: [{ ticketsAvailable: 0, totalTickets: 200, currentPrice: 50 }],
    }
    render(<EventCard event={soldOutEvent} onClick={() => {}} />)
    expect(screen.getByText('Sold Out')).toBeInTheDocument()

    const lowEvent = {
      ...mockEvent,
      ticketTiers: [{ ticketsAvailable: 10, totalTickets: 200, currentPrice: 50 }],
    }
    render(<EventCard event={lowEvent} onClick={() => {}} />)
    expect(screen.getByText('Low')).toBeInTheDocument()
  })

  it('handles missing image gracefully with fallback', () => {
    const noImageEvent = { ...mockEvent, bannerUrl: undefined }
    render(<EventCard event={noImageEvent} onClick={() => {}} />)
    const img = screen.getByAltText('Lagos Jazz Night')
    expect(img).toHaveAttribute('src', '/placeholder-event.jpg')
  })

  it('passes accessibility audit', async () => {
    const { container } = render(<EventCard event={mockEvent} onClick={() => {}} />)
    const results = await axe(container)
    expect(results).toHaveNoViolations()
  })

  it('has proper keyboard navigation and ARIA attributes', () => {
    render(<EventCard event={mockEvent} onClick={() => {}} />)

    const card = screen.getByRole('button', { name: /lagos jazz night event by amaka okonkwo/i })
    expect(card).toBeInTheDocument()
    expect(card).toHaveAttribute('tabIndex', '0')
    expect(card).toHaveAttribute('aria-label', expect.stringContaining('Lagos Jazz Night'))
  })

  it('has tooltip for truncated title', () => {
    render(<EventCard event={mockEvent} onClick={() => {}} />)

    const title = screen.getByText('Lagos Jazz Night')
    expect(title).toHaveAttribute('title', 'Lagos Jazz Night')
  })
})