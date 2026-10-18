import React, { useState } from 'react';
import { Category } from '../../types';
// eslint-disable-next-line react/no-unknown-property

const desktopSidebar = ({
  categories,
  selectedCategory,
  onCategoryChange,
  priceRange,
  onPriceChange,
  location,
  onLocationChange,
  onClearFilters,
}: {
  categories: Category[];
  selectedCategory: string | null;
  onCategoryChange: (category: string | null) => void;
  priceRange: { min: number; max: number };
  onPriceChange: (range: { min: number; max: number }) => void;
  location: string;
  onLocationChange: (location: string) => void;
  onClearFilters: () => void;
}) => (
  <div className="sticky top-0 h-screen p-6 max-w-sm">
    <h2 className="text-xl font-semibold mb-6">Filters</h2>

    <div className="mb-4">
      <label className="block text-sm font-medium mb-2">Category</label>
      <select
        onChange={(e) => onCategoryChange(e.target.value || null)}
        className="w-full rounded border px-3 py-2"
      >
        <option value="">All Categories</option>
        {categories.map((cat) => (
          <option
            key={cat}
            value={cat}
            selected={selectedCategory === cat}
          >
            {cat}
          </option>
        ))}
      </select>
    </div>

    <div className="mb-4">
      <label className="block text-sm font-medium mb-2">Price Range</label>
      <div className="flex items-center justify-between mb-2">
        <span className="text-xs text-gray-600">Min: €{priceRange.min}</span>
        <span className="text-xs text-gray-600">Max: €{priceRange.max}</span>
      </div>
      <input
        type="range"
        min={priceRange.min}
        max={priceRange.max}
        value={priceRange.min}
        onChange={(e) =>
          onPriceChange({ min: parseFloat(e.target.value), max: priceRange.max })
        }
        className="w-full rounded border my-2"
      />
      <input
        type="range"
        min={priceRange.min}
        max={priceRange.max}
        value={priceRange.max}
        onChange={(e) =>
          onPriceChange({ min: priceRange.min, max: parseFloat(e.target.value) })
        }
        className="w-full rounded border my-2"
      />
    </div>

    <div className="mb-4">
      <label className="block text-sm font-medium mb-2">Location</label>
      <input
        type="text"
        placeholder="Search location..."
        value={location || ''}
        onChange={(e) => onLocationChange(e.target.value || '')}
        className="w-full rounded border px-3 py-2"
      />
    </div>

    <button
      onClick={onClearFilters}
      className="w-full mt-4 rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-100"
    >
      Clear Filters
    </button>
  </div>
);

const mobileDrawer = ({
  isOpen,
  setIsOpen,
  categories,
  selectedCategory,
  onCategoryChange,
  priceRange,
  onPriceChange,
  location,
  onLocationChange,
  onClearFilters,
}: {
  isOpen: boolean;
  setIsOpen: React.Dispatch<React.SetStateAction<boolean>>;
  categories: Category[];
  selectedCategory: string | null;
  onCategoryChange: (category: string | null) => void;
  priceRange: { min: number; max: number };
  onPriceChange: (range: { min: number; max: number }) => void;
  location: string;
  onLocationChange: (location: string) => void;
  onClearFilters: () => void;
}) => {
  return (
    <div className="fixed inset-0 bg-black/60 z-50 backdrop-blur-sm">
      <div className="fixed top-0 right-0 w-64 h-screen bg-white shadow-2L transform transition-transform rtl:translate-y-right rtl:left-auto rtl:right-0 md:translateX0 md:translateY0" style={{ transform: isOpen ? 'translateX(0)' : 'translateX(100%)' }}>
        <button
          onClick={() => setIsOpen(false)}
          className="absolute top-3 right-3 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center"
        >
          <svg
            className="h-4 w-4"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>

        <h2 className="text-xl font-semibold p-4">Filters</h2>

        <nav className="p-4">
          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Category</label>
            <select
              onChange={(e) =>
                onCategoryChange(e.target.value || null)
              }
              className="w-full rounded border px-3 py-2"
            >
              <option value="">All Categories</option>
              {categories.map((cat) => (
                <option
                  key={cat}
                  value={cat}
                  selected={selectedCategory === cat}
                >
                  {cat}
                </option>
              ))}
            </select>
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Price Range</label>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs text-gray-600">Min: €{priceRange.min}</span>
              <span className="text-xs text-gray-600">Max: €{priceRange.max}</span>
            </div>
            <input
              type="range"
              min={priceRange.min}
              max={priceRange.max}
              value={priceRange.min}
              onChange={(e) =>
                onPriceChange({ min: parseFloat(e.target.value), max: priceRange.max })
              }
              className="w-full rounded border my-2"
            />
            <input
              type="range"
              min={priceRange.min}
              max={priceRange.max}
              value={priceRange.max}
              onChange={(e) =>
                onPriceChange({ min: priceRange.min, max: parseFloat(e.target.value) })
              }
              className="w-full rounded border my-2"
            />
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium mb-2">Location</label>
            <input
              type="text"
              placeholder="Search location..."
              value={location || ''}
              onChange={(e) => onLocationChange(e.target.value || '')}
              className="w-full rounded border px-3 py-2"
            />
          </div>

          <button
            onClick={onClearFilters}
            className="w-full rounded border px-3 py-2 text-sm text-red-600 hover:bg-red-100 mt-4"
          >
            Clear Filters
          </button>
        </nav>
      </div>
    </div>
  );
};

const FilterSidebar = ({
  categories = [],
  selectedCategory,
  onCategoryChange,
  priceRange,
  onPriceChange,
  location,
  onLocationChange,
  onClearFilters,
}: {
  categories: Category[];
  selectedCategory: string | null;
  onCategoryChange: (category: string | null) => void;
  priceRange: { min: number; max: number };
  onPriceChange: (range: { min: number; max: number }) => void;
  location: string;
  onLocationChange: (location: string) => void;
  onClearFilters: () => void;
}) => {
  const [isMobileOpen, setIsMobileOpen] = useState(false);

  const handleCategoryChange = (category: string | null) => {
    onCategoryChange(category);
    if (isMobileOpen) setIsMobileOpen(false);
  };

  const handlePriceChange = (range: { min: number; max: number }) => {
    onPriceChange(range);
  };

  const handleLocationChange = (loc: string) => {
    onLocationChange(loc);
  };

  return (
    <div className="w-full bg-white shadow-sm border-r border-gray-200">
      {isMobileOpen ? mobileDrawer({
        isOpen: isMobileOpen,
        setIsOpen: setIsMobileOpen,
        categories,
        selectedCategory,
        onCategoryChange: handleCategoryChange,
        priceRange,
        onPriceChange: handlePriceChange,
        location,
        onLocationChange: handleLocationChange,
        onClearFilters,
      }) : desktopSidebar({
        categories,
        selectedCategory,
        onCategoryChange: handleCategoryChange,
        priceRange,
        onPriceChange: handlePriceChange,
        location,
        onLocationChange: handleLocationChange,
        onClearFilters,
      })}
    </div>
  );
};

export default FilterSidebar;