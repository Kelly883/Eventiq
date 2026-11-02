import React, { useState, useCallback, useEffect, useMemo, useRef } from 'react';
import toast from 'react-hot-toast';
import { formatDistanceToNow } from 'date-fns';
import { useSearchParams } from 'react-router-dom';
import { useNavigate } from 'react-router-dom';
import { useReducedMotion } from '../hooks/useReducedMotion';
import { useDebounce } from '../hooks/useDebounce';

interface ActivityFilters {
  event?: string;
  tier?: string;
  dateFrom?: string;
  dateTo?: string;
}

const FILTER_KEYS: Array<keyof ActivityFilters> = ['event', 'tier', 'dateFrom', 'dateTo'];

// Debounced filters state - 300ms debounce to reduce API calls
const debouncedFilters = useDebounce(filters, 300);

// ... rest of the component using debouncedFilters instead of filters for API calls