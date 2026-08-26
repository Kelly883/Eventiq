import React from 'react';
import { Link } from 'react-router-dom';

const CategorySection = () => {
  const categories = [
    { name: 'Concerts', icon: '♪', count: 234, color: '#FF6B6B' },
    { name: 'Festivals', icon: '◈', count: 45, color: '#4ECDC4' },
    { name: 'Comedy', icon: '☺', count: 89, color: '#FFDA6B' },
    { name: 'Theatre & Arts', icon: '◆', count: 67, color: '#9B59B6' },
    { name: 'Conferences', icon: '▣', count: 156, color: '#3498DB' },
    { name: 'Sports', icon: '⚡', count: 78, color: '#E74C3C' },
  ];

  return (
    <section className="category-section">
      <div className="section-container">
        <div className="section-header">
          <h2 className="section-title">Browse by Category</h2>
        </div>
        <div className="categories-grid">
          {categories.map((category) => (
            <Link
              key={category.name}
              to={`/events?category=${category.name.toLowerCase()}`}
              className="category-card"
            >
              <span className="category-icon" style={{ color: category.color }}>
                {category.icon}
              </span>
              <span className="category-name">{category.name}</span>
              <span className="category-count">{category.count} events</span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
};

export default CategorySection;
