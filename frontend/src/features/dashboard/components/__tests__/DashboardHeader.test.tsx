import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { DashboardHeader } from '../dashboard/components/dashboard/DashboardHeader'

describe('DashboardHeader', () => {
  const mockOnCreateEvent = jest.fn()
  const mockOnFilterChange = jest.fn()
  const mockOnDateRangeChange = jest.fn()

  it('shows Create New Event button, filter dropdown, date range picker, and live indicator', () => {
    render(
      <DashboardHeader
        user={{ name: 'John Doe' }}
        onFilterChange={mockOnFilterChange}
        onDateRangeChange={mockOnDateRangeChange}
        onCreateEvent={mockOnCreateEvent}
        filterOptions={[
          { value: 'all', label: 'All Events' },
          { value: 'concert', label: 'Concerts' },
          { value: 'summit', label: 'Summits' },
          { value: 'workshop', label: 'Workshops' },
        ]}
      />
    )

    expect(screen.getByText('Create New Event')).toBeInTheDocument()
    expect(screen.getByText('John Doe')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /all events/i })).toBeInTheDocument()
    expect(screen.getByRole('combobox', { name: /date range/i })).toBeInTheDocument()
    expect(screen.getByText(/live/i) || screen.getByRole('img', { name: /live indicator/i })).toBeInTheDocument()
  })

  it('filter dropdown and date range picker callbacks fire', () => {
    render(
      <DashboardHeader
        user={{ name: 'John Doe' }}
        onFilterChange={mockOnFilterChange}
        onDateRangeChange={mockOnDateRangeChange}
        onCreateEvent={() => {}}
        filterOptions={[
          { value: 'all', label: 'All Events' },
          { value: 'concert', label: 'Concerts' },
        ]}
      />
    )

    // Change filter
    const filterSelect = screen.getByRole('combobox', { name: /filter by category/i })
    if (filterSelect) {
      fireEvent.change(filterSelect, { target: { value: 'concert' } })
      expect(mockOnFilterChange).toHaveBeenCalledWith('concert')
    }

    // Change date range
    const dateSelect = screen.getByRole('combobox', { name: /select date range/i })
    if (dateSelect) {
      fireEvent.change(dateSelect, { target: { value: '30d' } })
      expect(mockOnDateRangeChange).toHaveBeenCalled()
    }
  })
})
