import React, { useState, useEffect, useRef } from 'react';
import { api } from '../../../lib/api';
import { useNavigate } from 'react-router-dom';
import type { SearchSuggestion } from '../types/shared';

export interface SearchBarProps {
  onSearch?: (suggestion: SearchSuggestion) => void;
  placeholder?: string;
}

const DEBOUNCE_MS = 300;

const SearchBar = ({
  onSearch,
  placeholder = 'Search events…',
}: SearchBarProps) => {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    timeoutRef.current = setTimeout(async () => {
      const trimmed = query.trim();
      if (trimmed.length < 2) {
        setSuggestions([]);
        setIsOpen(false);
        return;
      }

      try {
        const res = await api.get('/events/public/search', {
          params: { q: trimmed },
        });
        const data = res.data?.data ?? [];
        const list = Array.isArray(data) ? data : [];
        setSuggestions(list);
        setIsOpen(list.length > 0);
      } catch (err) {
        console.error('Search failed', err);
        setSuggestions([]);
        setIsOpen(false);
      }
    }, DEBOUNCE_MS);

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, [query]);

  useEffect(() => {
    if (!isOpen || suggestions.length === 0 || !inputRef.current) return;

    const firstItem = document.querySelector(
      '.search-bar__dropdown-item',
    ) as HTMLElement | null;
    firstItem?.focus();
  }, [isOpen, suggestions]);

  const handleSelect = (suggestion: SearchSuggestion) => {
    setSuggestions([]);
    setQuery('');
    setIsOpen(false);
    onSearch?.(suggestion);
    navigate(`/events/${suggestion.id}`);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      setSuggestions([]);
      setIsOpen(false);
    }
  };

  const handleBlur = () => {
    // Delay close so click on suggestion can fire
    setTimeout(() => setIsOpen(false), 150);
  };

  return (
    <div className="search-bar" onKeyDown={handleKeyDown}>
      <div className="search-bar__input-wrapper">
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={placeholder}
          className="search-bar__input"
          minLength={2}
          aria-label="Search events"
          onBlur={handleBlur}
        />
        {isOpen && suggestions.length > 0 && (
          <div className="search-bar__dropdown" role="listbox" aria-label="Search suggestions">
            {suggestions.map((suggestion) => (
              <div
                key={suggestion.id}
                className="search-bar__dropdown-item"
                onClick={() => handleSelect(suggestion)}
                onMouseDown={(e) => e.preventDefault()}
                role="option"
                tabIndex={0}
                aria-label={suggestion.title}
              >
                <span className="search-bar__dropdown-title">
                  {suggestion.title || 'Untitled Event'}
                </span>
                <span className="search-bar__dropdown-meta">
                  {suggestion.start_datetime
                    ? new Date(suggestion.start_datetime).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                      })
                    : ''}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default SearchBar;
