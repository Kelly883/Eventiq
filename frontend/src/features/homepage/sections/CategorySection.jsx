import React from 'react';
import { Link } from 'react-router-dom';
import { useCategories } from '../hooks/useHomepageData';

const CategorySection = () => {
  const { data: categories = [], isLoading, isError } = useCategories();

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
    return categoryIcons[key] || categoryIcons[key.replace(/[^a-z]/g, '')] || '◆';
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

  // GET /categories returns {id, slug, name, events_count} objects (a real
  // per-category count, computed from published events -- not just a flat
  // list of names) -- not plain strings. Each card links to the browse
  // page filtered by the stable slug.
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
              to={`/events?category=${encodeURIComponent(category.slug)}`}
              className="category-card"
            >
              <span className="category-icon">
                {getCategoryIcon(category.name)}
              </span>
              <span className="category-name">{category.name}</span>
              <span className="category-count">
                {category.events_count} {category.events_count === 1 ? 'event' : 'events'}
              </span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
};

export default CategorySection;
