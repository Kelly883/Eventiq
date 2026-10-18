import React, { useState, useEffect, useCallback, useRef } from 'react';

const SearchBar = ({
  onSearch,
  onSelectResult,
  placeholder = 'Search events...',
}: {
  onSearch: (query: string) => void;
  onSelectResult: (event: any) => void;
  placeholder: string;
}) => {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<any[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const debounceTimer = useRef<ReturnType<typeof setTimeout>>();

  const debouncedSearch = useCallback((query: string) => {
    if (debounceTimer.current) {
      clearTimeout(debounceTimer.current);
    }
    debounceTimer.current = setTimeout(() => {
      setResults([]);
      if (query.trim().length < 2) {
        setIsOpen(false);
        return;
      }
      onSearch(query);
    }, 300);
  }, [onSearch]);

  useEffect(() => {
    return () => {
      if (debounceTimer.current) {
        clearTimeout(debounceTimer.current);
      }
    };
  }, []);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setQuery(value);
    debouncedSearch(value);

    if (value.trim().length === 0) {
      setIsOpen(false);
    }
  };

  const handleSelect = (event: any) => {
    onSelectResult(event);
    setIsOpen(false);
    setResults([]);
    setQuery(event.title || event.name || '');
  };

  return (
    <div className="relative w-full">
      <div className="flex items-center">
        <svg
          className="h-5 w-5 text-gray-400"
          viewBox="0 0 24 24"
          fill="currentColor"
        >
          <path
            d="M21 21l-6-6c-.45-.44-1.06-.44-1.5 0L5 21l5.15-4.72c.33-.3.33-.81 0-1.1l-5.66-3.33c-.33-.19-.33-.55 0-.73l5.16-3.07c.44-.26.44-.76 0-1.02L4.23 5.69c-.44-.45-.44-1.06 0-1.5l6-6c.44-.44.95-.44 1.39 0l6 6c.33.33.33.82 0 1.15z"
          />
        </svg>
        <input
          type="text"
          value={query}
          onChange={handleInputChange}
          placeholder={placeholder}
          className="ml-2 block w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          aria-label="Search events"
        />
      </div>

      {isOpen && results.length > 0 && (
        <div
          className="absolute top-full left-0 right-4 w-64 rounded border border-gray-300 bg-white shadow-lg z-10 max-h-64 overflow-y-auto"
          role="listbox"
        >
          {results.map((result) => (
            <div
              key={result.id || result.title}
              className="px-3 py-2 cursor-pointer hover:bg-gray-50"
              onClick={() => handleSelect(result)}
              role="option"
              aria-selected={false}
            >
              <div className="font-medium truncate">
                {result.title || result.name}
              </div>
              <div className="text-xs text-gray-500 truncate">
                {result.organizerName || result.category || ''}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SearchBar;