import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { api } from '../../../lib/api';
import EventCard from '../components/EventCard';

const EventBrowsePage = () => {
  const [searchParams] = useSearchParams();
  const q = searchParams.get('q') || '';
  const category = searchParams.get('category') || '';
  const filter = searchParams.get('filter') || '';

  const [events, setEvents] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  // Fetch events + categories whenever the URL-driven filters change. This is
  // what makes the homepage hero search, QuickFilters, and the footer's
  // ?filter= / ?category= links actually reach into the API instead of landing
  // on an always-unfiltered list.
  useEffect(() => {
    const params = new URLSearchParams();
    params.set('limit', '50');
    if (category) params.set('category', category);
    if (filter) params.set('filter', filter);
    if (q) params.set('search', q);

    let isActive = true;
    setLoading(true);
    setError(false);

    const query = params.toString();
    Promise.all([api.get(`/events?${query}`), api.get('/categories')])
      .then(([eventsRes, categoriesRes]) => {
        if (!isActive) return;
        setEvents(Array.isArray(eventsRes.data?.data) ? eventsRes.data.data : []);
        // GET /categories returns a plain array of category name strings.
        setCategories(Array.isArray(categoriesRes.data?.data) ? categoriesRes.data.data : []);
      })
      .catch(() => {
        if (!isActive) return;
        setError(true);
        setEvents([]);
        setCategories([]);
      })
      .finally(() => {
        if (isActive) setLoading(false);
      });

    return () => {
      isActive = false;
    };
  }, [q, category, filter]);

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">

        {/* Breadcrumb Navigation */}
        <nav className="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
          <ol className="flex flex-wrap items-center gap-2">
            <li className="flex items-center gap-1.5 text-sm text-slate-500">
              <Link to="/events" className="underline cursor-pointer text-indigo-600">
                Events
              </Link>
            </li>
            {category && (
              <>
                <li className="text-slate-400">/</li>
                <li className="flex items-center gap-1.5 text-sm text-slate-900">{category}</li>
              </>
            )}
          </ol>
        </nav>

        {/* Category Filters */}
        <div className="mb-6">
          <h2 className="text-sm font-medium text-slate-500 mb-2">Filter by Category</h2>
          <div className="flex flex-wrap gap-2">
            <Link
              to={filter ? `/events?filter=${filter}` : '/events'}
              className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs font-medium transition-colors ${
                !category
                  ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
                  : 'border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'
              }`}
            >
              All Events
            </Link>
            {categories.map((name) => {
              const isSelected = category === name;
              const base = filter ? `/events?filter=${filter}` : '/events';
              return (
                <Link
                  to={isSelected ? base : `${base}${base.includes('?') ? '&' : '?'}category=${encodeURIComponent(name)}`}
                  key={name}
                  className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs font-medium transition-colors ${
                    isSelected
                      ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
                      : 'border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'
                  }`}
                >
                  {name}
                </Link>
              );
            })}
          </div>
        </div>

        {/* Search summary + clear filter */}
        {(q || filter || category) && (
          <div className="mb-4 flex items-center gap-3 text-sm text-slate-600">
            <span>
              {events.length} result{events.length === 1 ? '' : 's'}
            </span>
            <Link to="/events" className="text-indigo-600 underline hover:text-indigo-800">
              Clear all filters
            </Link>
          </div>
        )}

        {/* Event Cards */}
        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="event-card skeleton">
                <div className="skeleton-image" />
                <div className="skeleton-content">
                  <div className="skeleton-title" />
                  <div className="skeleton-text" />
                  <div className="skeleton-text short" />
                </div>
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="col-span-full rounded-xl border border-red-200 bg-red-50 p-8 text-center text-sm text-red-600">
            <p>We couldn't load events right now. Please try again in a moment.</p>
          </div>
        ) : events.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {events.map((event) => (
              <EventCard key={event.id} event={event} />
            ))}
          </div>
        ) : (
          <div className="col-span-full p-10 text-center text-slate-500 rounded-xl border border-slate-200 bg-white">
            <p className="mb-1 font-medium text-slate-700">No events found.</p>
            <p className="mb-4">Try adjusting your search or filters.</p>
            <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">
              Browse all events
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default EventBrowsePage;
