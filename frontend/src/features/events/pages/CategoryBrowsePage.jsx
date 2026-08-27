import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../../../lib/api';
import EventCard from '../components/EventCard';

const CategoryBrowsePage = () => {
  const { categoryId } = useParams();
  const department = categoryId || '';

  const [events, setEvents] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  // The backend treats categories as plain strings (GET /categories returns
  // distinct event `category` column values), so the route param is the
  // category name itself, not an integer id.
  const categoryLabel = department;

  useEffect(() => {
    let isActive = true;
    setLoading(true);
    setError(false);

    const params = new URLSearchParams();
    params.set('limit', '50');
    if (department) params.set('category', department);

    Promise.all([api.get(`/events?${params.toString()}`), api.get('/categories')])
      .then(([eventsRes, categoriesRes]) => {
        if (!isActive) return;
        setEvents(Array.isArray(eventsRes.data?.data) ? eventsRes.data.data : []);
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
  }, [department]);

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        {/* Breadcrumb */}
        <nav className="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
          <ol className="flex flex-wrap items-center gap-2">
            <li className="flex items-center gap-1.5 text-sm text-slate-500">
              <Link to="/events" className="underline cursor-pointer text-indigo-600">
                Events
              </Link>
            </li>
            <li className="text-slate-400">/</li>
            <li className="flex items-center gap-1.5 text-sm text-slate-900">{categoryLabel}</li>
          </ol>
        </nav>

        {/* Category Header */}
        <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-6 mb-6">
          <h1 className="text-2xl font-bold text-indigo-600">{categoryLabel}</h1>
          <p className="text-slate-500">Browse events in this category.</p>
        </div>

        {/* Other categories */}
        <div className="mb-6">
          <h2 className="text-sm font-medium text-slate-500 mb-2">Browse other categories</h2>
          <div className="flex flex-wrap gap-2">
            {categories.map((name) => (
              <Link
                key={name}
                to={`/events?category=${encodeURIComponent(name)}`}
                className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs font-medium transition-colors ${
                  name === department
                    ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
                    : 'border-slate-200 text-slate-500 hover:border-slate-400 hover:text-slate-700'
                }`}
              >
                {name}
              </Link>
            ))}
          </div>
        </div>

        {/* Event Grid */}
        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="event-card skeleton">
                <div className="skeleton-image" />
                <div className="skeleton-content">
                  <div className="skeleton-title" />
                  <div className="skeleton-text" />
                </div>
              </div>
            ))}
          </div>
        ) : error ? (
          <div className="rounded-xl border border-red-200 bg-red-50 p-8 text-center text-sm text-red-600">
            <p>We couldn't load events right now. Please try again in a moment.</p>
          </div>
        ) : events.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {events.map((event) => (
              <EventCard key={event.id} event={event} />
            ))}
          </div>
        ) : (
          <div className="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500">
            <p className="mb-1 font-medium text-slate-700">No events found in this category.</p>
            <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">
              Back to All Events
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default CategoryBrowsePage;
