import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

const QuickFilters = () => {
  const [activeTimeFilter, setActiveTimeFilter] = useState('all');
  const [activeCategory, setActiveCategory] = useState(null);
  const navigate = useNavigate();

  const timeFilters = [
    { label: 'All', value: 'all' },
    { label: 'Today', value: 'today' },
    { label: 'This week', value: 'week' },
    { label: 'This month', value: 'month' },
  ];
  const categories = [
    { name: 'Concerts', slug: 'concerts' },
    { name: 'Festivals', slug: 'festivals' },
    { name: 'Comedy', slug: 'comedy' },
    { name: 'Theatre & Arts', slug: 'theatre' },
    { name: 'Conferences', slug: 'conferences' },
    { name: 'Sports', slug: 'sports' },
  ];

  const handleTimeFilter = (filter) => {
    setActiveTimeFilter(filter.value);
    setActiveCategory(null);
    if (filter.value === 'all') {
      navigate('/events');
    } else {
      navigate(`/events?sort=upcoming`);
    }
  };

  const handleCategoryFilter = (category) => {
    setActiveCategory(category.name);
    setActiveTimeFilter('all');
    navigate(`/events?category=${category.slug}`);
  };

  return (
    <section className="quick-filters">
      <div className="filters-container">
        <div className="time-filters">
          {timeFilters.map((filter) => (
            <button
              key={filter.value}
              className={`filter-chip ${activeTimeFilter === filter.value ? 'active' : ''}`}
              onClick={() => handleTimeFilter(filter)}
            >
              {filter.label}
            </button>
          ))}
        </div>
        <div className="category-filters">
          {categories.map((category) => (
            <button
              key={category.slug}
              className={`filter-chip category ${activeCategory === category.name ? 'active' : ''}`}
              onClick={() => handleCategoryFilter(category)}
            >
              {category.name}
            </button>
          ))}
        </div>
      </div>
    </section>
  );
};

export default QuickFilters;
