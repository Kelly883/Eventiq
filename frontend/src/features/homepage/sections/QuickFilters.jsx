import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

const QuickFilters = () => {
  const [activeTimeFilter, setActiveTimeFilter] = useState('all');
  const [activeCategory, setActiveCategory] = useState(null);
  const navigate = useNavigate();

  const timeFilters = ['All', 'Today', 'This week', 'This month'];
  const categories = ['Concerts', 'Festivals', 'Comedy', 'Theatre & Arts', 'Conferences', 'Sports'];

  // Previously these buttons only toggled their own `active` CSS class --
  // visually responsive, but clicking them did nothing else: no
  // navigation, no filtering, nothing downstream. Now they actually route
  // to /events with the selection applied.
  const handleTimeFilter = (filter) => {
    const value = filter.toLowerCase();
    setActiveTimeFilter(value);
    navigate(value === 'all' ? '/events' : `/events?when=${encodeURIComponent(value)}`);
  };

  const handleCategoryFilter = (category) => {
    setActiveCategory(category);
    navigate(`/events/category/${encodeURIComponent(category.toLowerCase())}`);
  };

  return (
    <section className="quick-filters">
      <div className="filters-container">
        <div className="time-filters">
          {timeFilters.map((filter) => (
            <button
              key={filter}
              className={`filter-chip ${activeTimeFilter === filter.toLowerCase() ? 'active' : ''}`}
              onClick={() => handleTimeFilter(filter)}
            >
              {filter}
            </button>
          ))}
        </div>
        <div className="category-filters">
          {categories.map((category) => (
            <button
              key={category}
              className={`filter-chip category ${activeCategory === category ? 'active' : ''}`}
              onClick={() => handleCategoryFilter(category)}
            >
              {category}
            </button>
          ))}
        </div>
      </div>
    </section>
  );
};

export default QuickFilters;
