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
          {categories.map((category) => {
            // categories comes from GET /categories, which returns
            // {id, slug, name, events_count} objects (needed for a genuine
            // per-category count and a stable slug for scopeByCategory
            // filtering) -- not plain strings. Treating each entry as a
            // string here would render an object as a React child (a
            // crash, same class of bug fixed earlier in TrendingSection/
            // UpcomingEventsSection's organizer field) and would produce a
            // garbage "[object Object]" URL via encodeURIComponent.
            const isActive = activeCategory === category.slug;
            return (
              <Link
                key={category.id}
                to={
                  isActive
                    ? '/events'
                    : `/events?category=${encodeURIComponent(category.slug)}`
                }
                aria-pressed={isActive}
                className={`filter-chip category ${isActive ? 'active' : ''}`}
              >
                {category.name}
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default QuickFilters;
