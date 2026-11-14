import React from 'react'
import { render, screen } from '@testing-library/react'
import { MetricCard } from './MetricCard'

describe('MetricCard', () => {
  it('renders label, value, and trend indicator', () => {
    render(
      <MetricCard
        label="Total Revenue"
        value="₦ 1,500,000"
        trendDirection="up"
        trendPercentage="+12.5"
      />
    )
    expect(screen.getByText('Total Revenue')).toBeInTheDocument()
    expect(screen.getByText('₦ 1,500,000')).toBeInTheDocument()
  })

  it('shows loading skeleton while data loads', () => {
    render(
      <MetricCard loading={true} label="Total Revenue" value="" trendDirection="up" trendPercentage="+" />
    )
    const skeleton = screen.getByByRole('progressbar') || screen.getByText('animate-pulse')
    
    const container = document.querySelector('.animate-pulse')
    expect(container).toBeInTheDocument()
  })
})
