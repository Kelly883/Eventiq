import React, { useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';
import type { DiscoveryCategory, PriceRange } from '../types/shared';
import { getCategoryIcon } from '../utils/categoryIcons';
import PriceRangeSlider from './PriceRangeSlider';

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

const CategoryChip = ({
  label,
  value,
  isSelected,
  onSelect,
}: {
  label: string;
  value: string;
  isSelected: boolean;
  onSelect: () => void;
}) => (
  <div
    className={`category-chip ${isSelected ? 'category-chip--selected' : ''}`}
    onClick={() => onSelect()}
    role="button"
    aria-checked={isSelected}
  >
    {getCategoryIcon(value)({ className: 'category-chip__icon', 'aria-hidden': true })}
    {label}
    {isSelected && (
      <span
        className="category-chip__remove"
        aria-label={`Remove ${label}`}
      >
        ×
      </span>
    )}
  </div>
);

export const FilterSidebar = ({
  currentFilters,
  onChange,
  availableCategories = [],
  priceRange,
}: FilterSidebarProps) => {
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isMobile, setIsMobile] = useState(
    typeof window !== 'undefined' ? window.innerWidth < 768 : false,
  );
  const [categories, setCategories] = useState<DiscoveryCategory[]>(availableCategories);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const handleResize = () => {
        setIsMobile(window.innerWidth < 768);
      };
      window.addEventListener('resize', handleResize);
      return () => window.removeEventListener('resize', handleResize);
    }
  }, []);

  useEffect(() => {
    if (categories.length === 0 && !availableCategories?.length) {
      api
        .get('/events/public/filters')
        .then((res) => {
          const cats = res.data?.data?.categories ?? [];
          setCategories(
            cats.map((c: DiscoveryCategory | string) =>
              typeof c === 'string' ? { id: c, slug: c, name: c } : c,
            ),
          );
        })
        .catch(() => setCategories([]));
    }
  }, [availableCategories, categories.length]);

  const [categoryValues, setCategoryValues] = useState<Set<string>>(
    new Set(
      currentFilters.category
        ? currentFilters.category.split(',').filter(Boolean)
        : [],
    ),
  );

  const minPriceDefault = priceRange?.min ?? 0;
  const maxPriceDefault = priceRange?.max ?? 100000;
  const minPrice = currentFilters.minPrice ?? minPriceDefault;
  const maxPrice = currentFilters.maxPrice ?? maxPriceDefault;

  const handleCategoryChange = (value: string) => {
    const values = new Set(categoryValues);
    if (values.has(value)) {
      values.delete(value);
    } else {
      values.add(value);
    }
    setCategoryValues(values);
    onChange({
      category: values.size > 0 ? Array.from(values).join(',') : undefined,
      dateRange: currentFilters.dateRange,
      minPrice,
      maxPrice,
      location: currentFilters.location,
    });
  };

  const handleDateChange = (value: string) => {
    setCategoryValues(new Set());
    onChange({
      category: currentFilters.category,
      dateRange: value,
      minPrice,
      maxPrice,
      location: currentFilters.location,
    });
  };

  const handlePriceChange = (value: [number, number]) => {
    onChange({
      category: currentFilters.category,
      dateRange: currentFilters.dateRange,
      minPrice: value[0],
      maxPrice: value[1],
      location: currentFilters.location,
    });
  };

  const handleLocationChange = (value: string) => {
    onChange({
      category: currentFilters.category,
      dateRange: currentFilters.dateRange,
      minPrice,
      maxPrice,
      location: value,
    });
  };

  const handleToggleDrawer = () => {
    setIsDrawerOpen(!isDrawerOpen);
  };

  const drawerContent = (
    <div className="filter-sidebar open" role="dialog" aria-modal="true" aria-label="Filters">
      <div className="filter-sidebar__header">
        <h3 className="filter-sidebar__title">Filters</h3>
        <button
          className="filter-sidebar__toggle"
          onClick={handleToggleDrawer}
          aria-label="Close filters"
        >
          ✕
        </button>
      </div>
      {drawerBody}
    </div>
  );

  const drawerBody = (
    <div className="filter-sidebar__content space-y-4">
      <div className="filter-sidebar__section">
        <h4 className="filter-sidebar__section-title">Category</h4>
        <div className="space-y-2 category-chips" role="group" aria-label="Categories">
          {categories.map((category) => (
            <CategoryChip
              key={category.id || category.slug}
              label={category.name}
              value={category.slug || category.id}
              isSelected={categoryValues.has(category.slug || category.id)}
              onSelect={() => handleCategoryChange(category.slug || category.id)}
            />
          ))}
          <button
            onClick={() => setCategoryValues(new Set())}
            className="add-category-btn"
            aria-label="Clear all categories"
          >
            Add Category
          </button>
        </div>
      </div>
      <div className="filter-sidebar__section">
        <h4 className="filter-sidebar__section-title">Date Range</h4>
        <div className="filter-sidebar__date-buttons space-y-2" role="radiogroup" aria-label="Date range filters">
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
        <PriceRangeSlider
          min={minPriceDefault}
          max={maxPriceDefault}
          value={[minPrice, maxPrice]}
          onChange={handlePriceChange}
          step={500}
          currency="NGN"
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
  );

  if (isMobile) {
    return (
      <>
        <button
          className="filter-sidebar__toggle"
          onClick={handleToggleDrawer}
          aria-label="Toggle filters"
          aria-expanded={isDrawerOpen}
        >
          ☰
        </button>
        {isDrawerOpen && (
          <div
            className="filter-sidebar open"
            role="dialog"
            aria-modal="true"
            aria-label="Filters"
            onClick={handleToggleDrawer}
          >
            {drawerContent}
          </div>
        )}
      </>
    );
  }

  return (
    <aside
      className="filter-sidebar"
      role="navigation"
      aria-label="Event filters"
    >
      <div className="filter-sidebar__header">
        <h3 className="filter-sidebar__title">Filters</h3>
      </div>
      {drawerContent}
    </aside>
  );
};

export default FilterSidebar;
