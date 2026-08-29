import React from 'react';
import { Link } from 'react-router-dom';
import { useCategories } from '../hooks/useHomepageData';

// Real SVG icons (24x24 outline) keyed by category slug. The previous emoji
// map looked generic and inconsistent on different OSes; SVGs render
// identically everywhere and are easier to recolor with the brand palette.
const ICONS = {
  concerts: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M9 18V5l12-2v13" />
      <circle cx="6" cy="18" r="3" />
      <circle cx="18" cy="16" r="3" />
    </svg>
  ),
  festivals: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M3 21l9-18 9 18" />
      <path d="M8 21V11h8v10" />
      <path d="M3 21h18" />
    </svg>
  ),
  comedy: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M8 14s1.5 2 4 2 4-2 4-2" />
      <circle cx="9" cy="10" r="0.6" fill="currentColor" stroke="none" />
      <circle cx="15" cy="10" r="0.6" fill="currentColor" stroke="none" />
    </svg>
  ),
  theater: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M3 3h18v3l-7 4v8l-2 1.5L10 18v-8L3 6z" />
    </svg>
  ),
  theatre: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M3 3h18v3l-7 4v8l-2 1.5L10 18v-8L3 6z" />
    </svg>
  ),
  arts: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M12 3a9 9 0 1 0 0 18c2 0 2-2 2-3s-1-2 0-3 2-1 3-1 3 0 3-2a9 9 0 0 0-8-9z" />
      <circle cx="8" cy="11" r="1.2" />
      <circle cx="12" cy="7" r="1.2" />
      <circle cx="16" cy="11" r="1.2" />
    </svg>
  ),
  conferences: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <rect x="3" y="4" width="18" height="14" rx="2" />
      <path d="M3 8h18" />
      <path d="M8 21h8" />
      <path d="M12 18v3" />
    </svg>
  ),
  sports: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="12" cy="12" r="9" />
      <path d="M3 12h18" />
      <path d="M12 3a14 14 0 0 1 0 18" />
      <path d="M12 3a14 14 0 0 0 0 18" />
    </svg>
  ),
  nightlife: (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
      <path d="M8 11l1.5 1.5" />
    </svg>
  ),
};

const FALLBACK_ICON = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <rect x="3" y="3" width="18" height="18" rx="3" />
    <path d="M9 9h6v6H9z" />
  </svg>
);

const getCategoryIcon = (slug) => ICONS[(slug || '').toLowerCase()] || FALLBACK_ICON;

const CategorySection = () => {
  const { data: categories = [], isLoading, isError } = useCategories();

  if (isLoading) {
    return (
      <section className="category-section">
        <div className="section-container">
          <div className="section-header">
            <h2 className="section-title">Browse by category</h2>
            <p className="section-subtitle">Find what you love — from concerts to conferences.</p>
          </div>
          <div className="categories-grid">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="category-card skeleton">
                <div className="skeleton-icon" />
                <div className="skeleton-text" />
                <div className="skeleton-text short" />
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
          <div>
            <h2 className="section-title">Browse by category</h2>
            <p className="section-subtitle">Find what you love — from concerts to conferences.</p>
          </div>
        </div>
        <div className="categories-grid">
          {categories.map((category) => (
            <Link
              key={category.id}
              to={`/events?category=${encodeURIComponent(category.slug)}`}
              className="category-card"
            >
              <span className="category-icon-wrap" aria-hidden="true">
                {getCategoryIcon(category.slug)}
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
