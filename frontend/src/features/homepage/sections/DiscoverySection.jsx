import React, { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { useCategories } from '../hooks/useHomepageData';

// Map a selected search date to the coarse calendar window the backend supports
// (today / week / month). Dates beyond a month are left unfiltered.
const dateToWindow = (dateString) => {
  if (!dateString) return null;
  const selected = new Date(`${dateString}T00:00:00`);
  if (Number.isNaN(selected.getTime())) return null;

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const diffDays = Math.round((selected - today) / 86400000);

  if (diffDays <= 0) return 'today';
  if (diffDays <= 7) return 'week';
  if (diffDays <= 31) return 'month';
  return null;
};

const DiscoverySection = () => {
  const [query, setQuery] = useState('');
  const [location, setLocation] = useState('');
  const [date, setDate] = useState('');
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const { data: categories = [] } = useCategories();

  const filter = searchParams.get('filter') || '';
  const activeCategory = searchParams.get('category') || '';

  const timeFilters = [
    { label: 'Any date', value: '' },
    { label: 'Today', value: 'today' },
    { label: 'This week', value: 'week' },
    { label: 'This month', value: 'month' },
  ];

  // Submitting navigates to /events with real filter params, which
  // EventBrowsePage turns into API calls (previously this form only called
  // preventDefault() and did nothing with anything the user typed or picked).
  const handleSearch = (e) => {
    e.preventDefault();

    const params = new URLSearchParams();
    if (query.trim()) params.set('q', query.trim());
    if (location.trim()) params.set('location', location.trim());

    // A selected date maps onto the supported today/week/month windows.
    const window = dateToWindow(date);
    if (window) params.set('filter', window);

    const qs = params.toString();
    navigate(`/events${qs ? `?${qs}` : ''}`);
  };

  return (
    <section className="discovery-section" aria-label="Find events">
      <div className="discovery-container">
        <form className="search-form" onSubmit={handleSearch}>
          <div className="search-field search-main">
            <svg className="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
            <input
              type="text"
              placeholder="Search events, artists, or venues"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              className="search-input"
            />
          </div>
          <div className="search-field">
            <svg className="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
            <input
              type="text"
              placeholder="Near you"
              value={location}
              onChange={(e) => setLocation(e.target.value)}
              className="search-input"
            />
          </div>
          <div className="search-field">
            <svg className="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <input
              type="date"
              aria-label="Any date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              className="search-input"
            />
          </div>
          <button type="submit" className="btn-search">
            Search
          </button>
        </form>

        <div className="discovery-filters">
          <span className="discovery-label">When</span>
          <div className="time-filters" role="group" aria-label="Filter by date">
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

          <span className="discovery-label">Category</span>
          <div className="category-filters" role="group" aria-label="Filter by category">
            {categories.map((category) => {
              // categories comes from GET /categories, which returns
              // {id, slug, name, events_count} objects — not plain strings.
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
      </div>
    </section>
  );
};

export default DiscoverySection;