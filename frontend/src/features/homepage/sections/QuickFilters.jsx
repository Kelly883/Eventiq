import React, { useState } from 'react';
import { Link } from 'react-router-dom';

const QuickFilters = () => {
  const [activeTimeFilter, setActiveTimeFilter] = useState('all');
  const [activeCategory, setActiveCategory] = useState(null);

  const timeFilters = ['All', 'Today', 'This week', 'This month'];
  const categories = ['Concerts', 'Festivals', 'Comedy', 'Theatre & Arts', 'Conferences', 'Sports'];

  return (
    <section className="quick-filters">
      <div className="filters-container">
        <div className="time-filters">
          {timeFilters.map((filter) => (
            <button
              key={filter}
              className={`filter-chip ${activeTimeFilter === filter.toLowerCase() ? 'active' : ''}`}
              onClick={() => setActiveTimeFilter(filter.toLowerCase())}
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
              onClick={() => setActiveCategory(category)}
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
