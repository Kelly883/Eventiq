import React, { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';

const EventBrowsePage = () => {
  const [events, setEvents] = useState([]);
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const navigate = useNavigate();
  const location = useLocation();

  // Fetch events and categories on mount
  useEffect(() => {
    async function fetchData() {
      try {
        const eventsRes = await api.get('/events');
        const categoriesRes = await api.get('/categories');
        setEvents(eventsRes.data.data || []);
        setCategories(categoriesRes.data.data || []);
      } catch (err) {
        console.error('Failed to fetch events/categories', err);
        setEvents([]);
        setCategories([]);
      }
    }
    fetchData();
  }, []);

  // Breadcrumb state
  const [breadcrumb, setBreadcrumb] = useState(['Events']);

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">

        {/* Breadcrumb Navigation */}
        <nav className="bg-white rounded-xl border border-slate-200 p-4 mb-6 shadow-sm">
          <ol className="flex flex-wrap items-center gap-2">
            {breadcrumb.map((segment, index) => {
              const isLast = index === breadcrumb.length - 1;
              return (
                <li key={index} className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 transition-colors">
                  {isLast ? (
                    <span>{segment}</span>
                  ) : (
                    <Link
                      to={index === 0 ? '/events' : '/events'}
                      className="underline cursor-pointer"
                    >
                      {segment}
                    </Link>
                  )}
                </li>
              );
            })}
          </ol>
        </nav>

        {/* Filterable Event Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

          {/* Category Filters */}
          {categories.length > 0 && (
            <div className="mb-6">
              <h2 className="text-sm font-medium text-slate-500 mb-2">Filter by Category</h2>
              <div className="flex flex-wrap gap-2">
                <Link
                  to="/events"
                  className="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-slate-200 text-xs font-medium text-slate-500 hover:border-slate-400 hover:text-slate-700 transition-colors"
                >
                  All Events
                </Link>
                {categories.map((cat) => {
                  const isSelected = selectedCategory?.id === cat.id;
                  return (
                    <Link
                      to={`/events/category/${cat.id}`}
                      key={cat.id}
                      className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-md border ${
                        isSelected
                          ? 'border-indigo-600 text-indigo-600 bg-indigo-50'
                          : 'border-slate-200 text-slate-500'
                      } transition-colors`}
                    >
                      {cat.name}
                    </Link>
                  );
                })}
              </div>
            </div>
          )}

          {/* Event Cards */}
          {events.length > 0 ? events.filter((event) => {
            // Filter by selected category if applicable
            if (selectedCategory && event.categoryId !== selectedCategory.id) return false;
            return true;
          }).map((event) => {
            const isExpanded = selectedCategory ? event.categoryId === selectedCategory.id : true;
            return (
              <div
                key={event.id}
                className={`border rounded-lg overflow-hidden transition-all cursor-pointer ${
                  isExpanded ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-slate-100 hover:border-slate-200'
                }`}
                onClick={() => navigate(`/events/${event.id}`)}
              >
                <div className="h-48 w-full bg-gradient-to-b from-indigo-100 to-slate-100 flex items-center justify-center text-xs text-slate-400">
                  Event Image
                </div>
                <div className="p-4">
                  <h3 className="font-medium text-slate-800 line-clamp-2">{event.name || event.title || `Event #${event.id}`}</h3>
                  <p className="text-xs text-slate-500 line-clamp-1 mt-1">
                    {event.description || 'No description available'}
                  </p>
                </div>
              </div>
            );
          }) : (
            <div className="col-span-full p-8 text-center text-slate-500">
              <p>No events found. <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">Browse Events</Link> to discover upcoming events.</p>
            </div>
          )}
        </div>

        {/* Empty state for no events */}
        {events.length === 0 && !categories.length && (
          <div className="col-span-full p-8 text-center text-slate-500">
            <p>No events available at this time.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default EventBrowsePage;
