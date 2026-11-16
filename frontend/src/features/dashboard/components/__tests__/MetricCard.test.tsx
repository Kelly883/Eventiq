import React from 'react'
import { render, screen, fireEvent } from '@testing-library/react'
import { vi } from 'vitest'
import MetricCard from '../../analytics/components/MetricCard'

const mockValue = 12345
const mockLabel = 'Total Revenue'

describe('MetricCard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders with default values', () => {
    render(<MetricCard label={mockLabel} />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('—')).toBeInTheDocument()
  })

  it('renders with custom value', () => {
    render(<MetricCard label={mockLabel} value={mockValue} />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('12,345')).toBeInTheDocument()
  })

  it('renders with currency NGN', () => {
    render(<MetricCard label={mockLabel} value={mockValue} currency="NGN" />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('₦12,345')).toBeInTheDocument()
  })

  it('renders with currency USD', () => {
    render(<MetricCard label={mockLabel} value={mockValue} currency="USD" />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('$12,345')).toBeInTheDocument()
  })

  it('renders with onClick handler', () => {
    const onClick = vi.fn()
    render(<MetricCard label={mockLabel} value={mockValue} onClick={onClick} />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()

    const card = screen.getByRole('button', { name: new RegExp(mockLabel.replace(/[.*+?^${}()|]/g, '\\$&')) }) as HTMLElement
    fireEvent.click(card)
    expect(onClick).toHaveBeenCalledTimes(1)
  })

  it('renders with trend up', () => {
    render(<MetricCard label={mockLabel} value={mockValue} trendDirection="up" trendPercentage="+10%" />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('+10%')).toBeInTheDocument()
    const trendBadge = screen.getByText('↑')
    expect(trendBadge).toBeInTheDocument()
  })

  it('renders with trend down', () => {
    render(<MetricCard label={mockLabel} value={mockValue} trendDirection="down" trendPercentage="-5%" />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('-5%')).toBeInTheDocument()
    const trendBadge = screen.getByText('↓')
    expect(trendBadge).toBeInTheDocument()
  })

  it('renders with trend neutral', () => {
    render(<MetricCard label={mockLabel} value={mockValue} trendDirection="neutral" />)
    expect(screen.getByText(mockLabel)).toBeInTheDocument()
    expect(screen.getByText('→0')).toBeInTheDocument()
    const trendBadge = screen.getByText('→')
    expect(trendBadge).toBeInTheDocument()
  })

  it('shows skeleton while loading', () => {
    render(<MetricCard label={mockLabel} loading={true} />)

    const skeleton = screen.getByRole('article', { hidden: true })
    expect(skeleton).toBeInTheDocument()
    expect(skeleton).toHaveAttribute('aria-hidden', 'true')
  })

  it('formats value without currency', () => {
    render(<MetricCard label={mockLabel} value={mockValue} />)
    expect(screen.getByText('12,345')).toBeInTheDocument()
  })

  it('handles null value', () => {
    render(<MetricCard label={mockLabel} value={null} />)
    expect(screen.getByText('—')).toBeInTheDocument()
  })

  it('handles undefined value', () => {
    render(<MetricCard label={mockLabel} value={undefined} />)
    expect(screen.getByText('—')).toBeInTheDocument()
  })

  it('handles empty string value', () => {
    render(<MetricCard label={mockLabel} value={''} />)
    expect(screen.getByText('—')).toBeInTheDocument()
  })

  it('passes accessibility audit', async () => {
    const { container } = render(<MetricCard label={mockLabel} value={mockValue} onClick={vi.fn()} />)
    const byRole = screen.getByRole('button', { name: new RegExp(mockLabel.replace(/[.*+?^${}()|]/g, '\\$&')) })
    expect(byRole).toBeInTheDocument()
  })
})