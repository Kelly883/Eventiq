import React from 'react';
import { Link } from 'react-router-dom';
import { useCategories } from '../hooks/useHomepageData';

const CategorySection = () => {
  const { data: categories, isLoading, isError } = useCategories();

  const categoryIcons = {
    concerts: '♪',
    festivals: '◈',
    comedy: '☺',
    theatre: '◆',
    theater: '◆',
    arts: '◆',
    conferences: '▣',
    sports: '⚡',
  };

  const getCategoryIcon = (name) => {
    const key = name?.toLowerCase() || '';
    return categoryIcons[key] || '◆';
  };

  if (isLoading) {
    return (
      <section className="category-section">
        <div className="section-container">
          <div className="section-header">
            <h2 className="section-title">Browse by Category</h2>
          </div>
          <div className="categories-grid">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="category-card skeleton">
                <div className="skeleton-icon" />
                <div className="skeleton-text" />
              </div>
            ))}
          </div>
        </div>
      </section>
    );
  }

  if (isError || !categories?.length) {
    return null;
  }

  return (
    <section className="category-section">
      <div className="section-container">
        <div className="section-header">
          <h2 className="section-title">Browse by Category</h2>
        </div>
        <div className="categories-grid">
          {categories.map((category) => (
            <Link
              key={category.id}
              to={`/events?category=${category.slug || category.name?.toLowerCase()}`}
              className="category-card"
            >
              <span className="category-icon">
                {getCategoryIcon(category.name)}
              </span>
              <span className="category-name">{category.name}</span>
              <span className="category-count">{category.events_count || 0} events</span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
};

export default CategorySection;
