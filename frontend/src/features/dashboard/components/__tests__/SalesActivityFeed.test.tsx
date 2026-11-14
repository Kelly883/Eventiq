import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { axe, toHaveNoViolations } from 'jest-axe'
import { SalesActivityFeed } from '../SalesActivityFeed'
import { vi } from 'vitest'
import { BrowserRouter } from 'react-router-dom'

expect.extend(toHaveNoViolations);

const renderWithRouter = (ui: React.ReactElement) => {
  return render(<BrowserRouter>{ui}</BrowserRouter>)
}

describe('SalesActivityFeed', () => {
  const mockActivities = [
    {
      id: 'act-1',
      eventName: 'Tech Conference 2024',
      tier: 'Early Bird',
      quantity: 2,
      price: 5000,
      timestamp: new Date(),
      eventStatus: 'live',
      tierBadge: 'early-bird',
    },
    {
      id: 'act-2',
      eventName: 'Music Festival',
      tier: 'VIP',
      quantity: 1,
      price: 15000,
      timestamp: new Date(Date.now() - 3600000),
      eventStatus: 'completed',
      tierBadge: 'vip',
    },
  ]

  it('shows activity items with event badge, tier, quantity, price, timestamp', () => {
    renderWithRouter(
      <SalesActivityFeed
        activities={mockActivities}
        filters={{}}
      />
    )

    expect(screen.getByText('Tech Conference 2024')).toBeInTheDocument()
    expect(screen.getByText('Early Bird')).toBeInTheDocument()
    expect(screen.getByText('2')).toBeInTheDocument()
    expect(screen.getByText('₦ 5,000')).toBeInTheDocument()
    expect(screen.getByText('Music Festival')).toBeInTheDocument()
    expect(screen.getByText('VIP')).toBeInTheDocument()
  })

  it('filters work (by event)', () => {
    const onFilterChange = vi.fn()

    renderWithRouter(
      <SalesActivityFeed
        activities={mockActivities}
        filters={{ event: 'Tech Conference 2024' }}
        onFilterChange={onFilterChange}
      />
    )

    const eventSelect = screen.getByRole('combobox', { name: /event/i })
    if (eventSelect) {
      fireEvent.change(eventSelect, { target: { value: 'Tech Conference 2024' } })
      expect(onFilterChange).toHaveBeenCalled()
    }
  })

  it('exports activity to CSV', () => {
    const mockDownload = vi.fn()

    renderWithRouter(
      <SalesActivityFeed
        activities={mockActivities}
        filters={{}}
        onDownloadCsv={mockDownload}
      />
    )

    const exportBtn = screen.getByRole('button', { name: /export activity/i })
    if (exportBtn) {
      fireEvent.click(exportBtn)
      expect(mockDownload).toHaveBeenCalled()
    }
  })

  it('passes accessibility audit', async () => {
    const { container } = renderWithRouter(
      <SalesActivityFeed
        activities={mockActivities}
        filters={{}}
      />
    )
    const results = await axe(container)
    expect(results).toHaveNoViolations()
  })

  it('has proper keyboard navigation for filter controls', () => {
    renderWithRouter(
      <SalesActivityFeed
        activities={mockActivities}
        filters={{}}
      />
    )

    const eventFilter = screen.getByRole('combobox', { name: /event/i })
    expect(eventFilter).toBeInTheDocument()
    expect(eventFilter).toHaveAttribute('tabIndex', '0')

    const exportBtn = screen.getByRole('button', { name: /export activity/i })
    expect(exportBtn).toBeInTheDocument()
    expect(exportBtn).not.toBeDisabled()
  })
})