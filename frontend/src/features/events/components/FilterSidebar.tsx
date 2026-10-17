import React, { useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';
import type { DiscoveryCategory, PriceRange } from '../types/shared';

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
  availableCategories?: DiscoveryCategory[];
  priceRange?: PriceRange;
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
  }, [availableCategories?.length]);

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

  const handleCategoryChange = useCallback(
    (value: string) => {
      setCategoryValues((prev) => {
        const next = new Set(prev);
        if (next.has(value)) {
          next.delete(value);
        } else {
          next.add(value);
        }
        return next;
      });
      setCategoryValues((prev) => {
        const next = new Set(prev);
        if (next.has(value)) next.delete(value);
        else next.add(value);
        const cat = Array.from(next).length > 0 ? Array.from(next).join(',') : undefined;
        onChange({ ...currentFilters, category: cat });
        return next;
      });
    },
    [currentFilters, onChange],
  );

  const handleDateChange = useCallback(
    (dateRange: string) => {
      onChange({ ...currentFilters, dateRange });
    },
    [currentFilters, onChange],
  );

  const handleMinPriceChange = useCallback(
    (value: number) => {
      onChange({ ...currentFilters, minPrice: value });
    },
    [currentFilters, onChange],
  );

  const handleMaxPriceChange = useCallback(
    (value: number) => {
      onChange({ ...currentFilters, maxPrice: value });
    },
    [currentFilters, onChange],
  );

  const handleLocationChange = useCallback(
    (location: string) => {
      onChange({ ...currentFilters, location: location || undefined });
    },
    [currentFilters, onChange],
  );

  const handleToggleDrawer = () => {
    setIsDrawerOpen((prev) => !prev);
  };

  const selectedCategoryName = currentFilters.category
    ? categories.find((c) => c.slug === currentFilters.category)?.name ||
      currentFilters.category
    : 'All Categories';

  const drawerContent = (
    <div className="filter-sidebar__content">
      <div className="filter-sidebar__section">
        <h4 className="filter-sidebar__section-title">Category</h4>
        <div className="category-select" onClick={() => {}} role="button" aria-haspopup="listbox" aria-expanded={categoryValues.size > 0}>
          <span className="category-select__trigger">{selectedCategoryName}</span>
          <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M6 9l6 6 6-6" />
          </svg>
        </div>
      </div>
      <div className="filter-sidebar__section">
        <h4 className="filter-sidebar__section-title">Date Range</h4>
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
            min={minPriceDefault}
            max={maxPriceDefault}
            step={500}
            value={minPrice}
            onChange={(e) => handleMinPriceChange(Number(e.target.value))}
            className="price-input__field"
            placeholder="Min"
          />
          <span className="price-input__separator">-</span>
          <input
            type="number"
            min={minPriceDefault}
            max={maxPriceDefault}
            step={500}
            value={maxPrice}
            onChange={(e) => handleMaxPriceChange(Number(e.target.value))}
            className="price-input__field"
            placeholder="Max"
          />
        </div>
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
          >
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
