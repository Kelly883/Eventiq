import React from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useCategories } from '../hooks/useHomepageData';

const QuickFilters = () => {
  const [searchParams] = useSearchParams();
  const filter = searchParams.get('filter') || '';
  const activeCategory = searchParams.get('category') || '';

  const { data: categories = [] } = useCategories();

  const timeFilters = [
    { label: 'All', value: '' },
    { label: 'Today', value: 'today' },
    { label: 'This week', value: 'week' },
    { label: 'This month', value: 'month' },
  ];

  // Chips are real <Link>s now so they navigate to a filtered browse page. The
  // category chips come from the live /categories endpoint, so the slug passed
  // exactly matches what EventBrowsePage will filter by (previously this
  // section only toggled local UI state and never influenced the results).
  return (
    <section className="quick-filters">
      <div className="filters-container">
        <div className="time-filters">
          {timeFilters.map(({ label, value }) => {
            const isActive = value === filter;
            return (
              <Link
                key={label}
                to={value ? `/events?filter=${value}` : '/events'}
                aria-pressed={isActive}
                className={`filter-chip ${isActive ? 'active' : ''}`}
              >
                {label}
              </Link>
            );
          })}
        </div>
        <div className="category-filters">
          {categories.map((name) => {
            const isActive = activeCategory === name;
            return (
              <Link
                key={name}
                to={
                  isActive
                    ? '/events'
                    : `/events?category=${encodeURIComponent(name)}`
                }
                aria-pressed={isActive}
                className={`filter-chip category ${isActive ? 'active' : ''}`}
              >
                {name}
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default QuickFilters;
