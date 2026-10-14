import React, { useState, useEffect, useRef } from 'react';
import { api } from '../../../lib/api';
import { useNavigate } from 'react-router-dom';
import type { Event } from '../types/shared';

export interface SearchBarProps {
  onSearch?: (event: Event) => void;
  placeholder?: string;
}

const DEBOUNCE_MS = 300;

const SearchBar = ({ onSearch, placeholder = 'Search events...' }: SearchBarProps) => {
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<Event[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const navigate = useNavigate();
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
    }

    timeoutRef.current = setTimeout(async () => {
      if (query.length < 2) {
        setSuggestions([]);
        setIsOpen(false);
        return;
      }

      try {
        const res = await api.get('/events/search', {
          params: { q: query },
        });
        const data = res.data.data || [];
        setSuggestions(Array.isArray(data) ? data : []);
        setIsOpen(true);
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

  const handleSelect = (event: Event) => {
    setSuggestions([]);
    setQuery('');
    onSearch?.(event);
    navigate(`/events/${event.id}`);
  };

  return (
    <div className="search-bar">
      <div className="search-bar__input-wrapper">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={placeholder}
          className="search-bar__input"
          minLength={2}
          aria-label="Search events"
        />
        {isOpen && suggestions.length > 0 && (
          <div className="search-bar__dropdown">
            {suggestions.map((event) => (
              <div
                key={event.id}
                className="search-bar__dropdown-item"
                onClick={() => handleSelect(event)}
                role="button"
                tabIndex={0}
                aria-label={event.title || event.organizer?.displayName || ''}
              >
                <span className="search-bar__dropdown-title">
                  {event.title || 'Untitled Event'}
                </span>
                <span className="search-bar__dropdown-meta">
                  {event.organizer?.displayName || ''}
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