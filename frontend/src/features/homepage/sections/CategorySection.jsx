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

  // GET /categories returns a plain array of category name strings (the
  // distinct `category` values across published events), not objects. Each
  // card links to the browse page filtered by that exact category.
  return (
    <section className="category-section">
      <div className="section-container">
        <div className="section-header">
          <h2 className="section-title">Browse by Category</h2>
        </div>
        <div className="categories-grid">
          {categories.map((name) => (
            <Link
              key={name}
              to={`/events?category=${encodeURIComponent(name)}`}
              className="category-card"
            >
              <span className="category-icon">
                {getCategoryIcon(name)}
              </span>
              <span className="category-name">{name}</span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
};

export default CategorySection;
