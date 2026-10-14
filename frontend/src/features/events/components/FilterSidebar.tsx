import React, { useState, useEffect } from 'react';
import { api } from '../../../lib/api';

export interface FilterOptions {
  categories: Array<{ id: number; slug: string; name: string; events_count: number }>;
  priceRange: { min: number; max: number };
}

export interface FilterSidebarProps {
  currentFilters: {
    category?: string;
    dateRange?: string;
    minPrice?: number;
    maxPrice?: number;
    location?: string;
  };
  onChange: (filters: {
    category?: string;
    dateRange?: string;
    minPrice?: number;
    maxPrice?: number;
    location?: string;
  }) => void;
  availableCategories?: FilterOptions['categories'];
}

const DateButton = ({
  label,
  value,
  isSelected,
  onClick,
}: {
  label: string;
  value: string;
  isSelected: boolean;
  onClick: () => void;
}) => (
  <button
    className={`date-filter-btn ${isSelected ? 'date-filter-btn--active' : ''}`}
    onClick={onClick}
    aria-pressed={isSelected}
  >
    {label}
  </button>
);

const renderCategoryOptions = (
  options: Array<{ label: string; value: string }>,
  selectedValues: Set<string>,
  onSelect: (value: string) => void,
) => {
  const elements: React.ReactNode[] = [];

  options.forEach((opt) => {
    const isSelected = selectedValues.has(opt.value);
    elements.push(
      <div
        key={opt.value}
        className={`category-chip ${isSelected ? 'category-chip--selected' : ''}`}
        style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}
        onClick={() => onSelect(opt.value)}
        role="button"
        aria-checked={isSelected}
      >
        {opt.label}
        {isSelected && (
          <span
            className="category-chip__remove"
            aria-label={`Remove ${opt.label}`}
          >
            ×
          </span>
        </div>
      );
    });
  });

  return elements;
};

export const FilterSidebar = ({
  currentFilters,
  onChange,
  availableCategories,
}: FilterSidebarProps) => {
  const [isMobile, setIsMobile] = useState(
    typeof window !== 'undefined' ? window.innerWidth < 768 : false,
  );

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const handleResize = () => {
        setIsMobile(window.innerWidth < 768);
      };
      window.addEventListener('resize', handleResize);
      return () => window.removeEventListener('resize', handleResize);
    }
  }, []);

  const [categories, setCategories] = useState<
    FilterOptions['categories']
  >(availableCategories ?? []);

  useEffect(() => {
    if (categories.length === 0) {
      fetch('/api/events/public/filters')
        .then((res) => res.json())
        .then((data) => setCategories(data.data?.categories ?? []))
        .catch(() => []);
    }
  }, [categories.length]);

  const categoryValues = new Set(
    (currentFilters.category ? currentFilters.category.split(',') : []) as string[],
  );
  const minPrice = currentFilters.minPrice ?? 0;
  const maxPrice = currentFilters.maxPrice ?? 100000;

  const handleCategoryChange = (value: string) => {
    if (categoryValues.has(value)) {
      categoryValues.delete(value);
    } else {
      categoryValues.add(value);
    }
    onChange({
      ...currentFilters,
      category: Array.from(categoryValues).length > 0 ? Array.from(categoryValues).join(',') : undefined,
    });
  };

  const handleDateChange = (dateRange: string) => {
    onChange({ ...currentFilters, dateRange });
  };

  const handleMinPriceChange = (value: number) => {
    onChange({ ...currentFilters, minPrice: value });
  };

  const handleMaxPriceChange = (value: number) => {
    onChange({ ...currentFilters, maxPrice: value });
  };

  const handleLocationChange = (location: string) => {
    onChange({ ...currentFilters, location });
  };

  if (isMobile) {
    return (
      <div className="filter-drawer">
        <div className="filter-drawer__header">
          <h3 className="filter-drawer__title">Filters</h3>
          <button
            className="filter-drawer__close"
            onClick={() => setIsMobile(!isMobile)}
            aria-label="Close filters"
          >
            ×
          </button>
        </div>
        <div className="filter-drawer__content">
          <div
            className="category-select"
            onClick={() => {}}
            role="button"
            aria-haspopup="listbox"
            aria-expanded={categoryValues.size > 0}
          >
            <span className="category-select__trigger">
              {currentFilters.category
                ? categories
                    .find((c) => c.slug === currentFilters.category)
                    ?.name || currentFilters.category
                : 'All Categories'}
            </span>
            <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </div>
          <div className="filter-drawer__date-buttons">
            {renderCategoryOptions(
              categories.map((c) => ({ label: c.name, value: c.slug })),
              categoryValues,
              handleCategoryChange,
            )}
          </div>
          <div className="filter-drawer__section">
            <h4 className="filter-drawer__section-title">Date</h4>
            <div className="filter-drawer__date-buttons">
              <DateButton
                label="Upcoming"
                value="upcoming"
                isSelected={currentFilters.dateRange === 'upcoming'}
                onClick={() => handleDateChange('upcoming')}
              />
              <DateButton
                label="This Week"
                value="this week"
                isSelected={currentFilters.dateRange === 'this week'}
                onClick={() => handleDateChange('this week')}
              />
              <DateButton
                label="This Month"
                value="this month"
                isSelected={currentFilters.dateRange === 'this month'}
                onClick={() => handleDateChange('this month')}
              />
              <DateButton
                label="Custom Range"
                value="custom"
                isSelected={currentFilters.dateRange === 'custom'}
                onClick={() => handleDateChange('custom')}
              />
            </div>
          </div>
          <div className="filter-drawer__section">
            <h4 className="filter-drawer__section-title">Price</h4>
            <div className="price-inputs">
              <input
                type="number"
                min={0}
                max={100000}
                step={500}
                value={minPrice}
                onChange={(e) => handleMinPriceChange(Number(e.target.value))}
                className="price-input__field"
                placeholder="Min"
              />
              <span className="price-input__separator">-</span>
              <input
                type="number"
                min={0}
                max={100000}
                step={500}
                value={maxPrice}
                onChange={(e) => handleMaxPriceChange(Number(e.target.value))}
                className="price-input__field"
                placeholder="Max"
              />
            </div>
          </div>
          <div className="filter-drawer__section">
            <h4 className="filter-drawer__section-title">Location</h4>
            <input
              type="text"
              value={currentFilters.location || ''}
              onChange={(e) => handleLocationChange(e.target.value)}
              placeholder="Venue or city"
              className="filter-drawer__location-input"
            />
          </div>
        </div>
      </div>
    );
  }

  return (
    <aside className="filter-sidebar">
      <div className="filter-sidebar__header">
        <h3 className="filter-sidebar__title">Filters</h3>
        <button
          className="filter-sidebar__toggle"
          onClick={() => setIsMobile(!isMobile)}
          aria-label="Toggle filters drawer"
        >
          ☰
        </button>
      </div>
      <div className="filter-sidebar__content">
        <div
          className="category-select"
          onClick={() => {}}
          role="button"
          aria-haspopup="listbox"
          aria-expanded={categoryValues.size > 0}
        >
          <span className="category-select__trigger">
            {currentFilters.category
              ? categories
                  .find((c) => c.slug === currentFilters.category)
                  ?.name || currentFilters.category
              : 'All Categories'}
          </span>
          <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M6 9l6 6 6-6" />
          </svg>
        </div>
        <div className="filter-sidebar__date-buttons">
          {renderCategoryOptions(
            categories.map((c) => ({ label: c.name, value: c.slug })),
            categoryValues,
            handleCategoryChange,
          )}
        </div>
        <div className="filter-sidebar__section">
          <h4 className="filter-sidebar__section-title">Date</h4>
          <div className="filter-sidebar__date-buttons">
            <DateButton
              label="Upcoming"
              value="upcoming"
              isSelected={currentFilters.dateRange === 'upcoming'}
              onClick={() => handleDateChange('upcoming')}
            />
            <DateButton
              label="This Week"
              value="this week"
              isSelected={currentFilters.dateRange === 'this week'}
              onClick={() => handleDateChange('this week')}
            />
            <DateButton
              label="This Month"
              value="this month"
              isSelected={currentFilters.dateRange === 'this month'}
              onClick={() => handleDateChange('this month')}
            />
            <DateButton
              label="Custom Range"
              value="custom"
              isSelected={currentFilters.dateRange === 'custom'}
              onClick={() => handleDateChange('custom')}
            />
          </div>
        </div>
        <div className="filter-sidebar__section">
          <h4 className="filter-sidebar__section-title">Price</h4>
          <div className="price-inputs">
            <input
              type="number"
              min={0}
              max={100000}
              step={500}
              value={minPrice}
              onChange={(e) => handleMinPriceChange(Number(e.target.value))}
              className="price-input__field"
              placeholder="Min"
            />
            <span className="price-input__separator">-</span>
            <input
              type="number"
              min={0}
              max={100000}
              step={500}
              value={maxPrice}
              onChange={(e) => handleMaxPriceChange(Number(e.target.value))}
              className="price-input__field"
              placeholder="Max"
            />
          </div>
          <div className="filter-sidebar__section">
            <h4 className="filter-sidebar__section-title">Location</h4>
            <input
              type="text"
              value={currentFilters.location || ''}
              onChange={(e) => handleLocationChange(e.target.value)}
              placeholder="Venue or city"
              className="filter-sidebar__location-input"
            />
          </div>
        </div>
      </div>
    </aside>
  );
};

export default FilterSidebar;