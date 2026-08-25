import React, { useEffect, useState } from 'react';
import { Link, useParams, useLocation, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';

const CategoryBrowsePage = () => {
  const { categoryId } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const [categories, setCategories] = useState([]);
  const [events, setEvents] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [breadcrumb, setBreadcrumb] = useState(['Events']);

  // Fetch categories and events on mount
  useEffect(() => {
    async function fetchData() {
      try {
        const categoriesRes = await api.get('/categories');
        const eventsRes = await api.get('/events');
        setCategories(categoriesRes.data.data || []);
        setEvents(eventsRes.data.data || []);
        // Set breadcrumb: 'Events > Category Name'
        if (selectedCategory) {
          setBreadcrumb(['Events', selectedCategory.name]);
        } else {
          setBreadcrumb(['Events']);
        }
      } catch (err) {
        console.error('Failed to fetch categories/events', err);
        setCategories([]);
        setEvents([]);
        setBreadcrumb(['Events']);
      }
    }
    fetchData();
  }, [categoryId]);

  // Update selected category from URL param
  useEffect(() => {
    if (categoryId) {
      const cat = categories.find((c) => c.id === parseInt(categoryId, 10));
      setSelectedCategory(cat || null);
      if (cat) {
        setBreadcrumb(['Events', cat.name]);
      }
    } else {
      setSelectedCategory(null);
      setBreadcrumb(['Events']);
    }
  }, [categoryId, categories]);

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
                      className="underline cursor-pointer text-indigo-600"
                    >
                      {segment}
                    </Link>
                  )}
                </li>
              );
            })}
          </ol>
        </nav>

        {/* Category Header */}
        {selectedCategory && (
          <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-6 mb-6">
            <h1 className="text-2xl font-bold text-indigo-600">{selectedCategory.name}</h1>
            <p className="text-slate-500">{selectedCategory.description || 'Category events'}</p>
          </div>
        )}

        {/* Event Grid for this Category */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {events.length > 0 ? events.map((event) => {
            // Only show events matching this category
            if (selectedCategory && event.categoryId !== selectedCategory.id) return null;
            return (
              <div
                key={event.id}
                className={`border rounded-lg overflow-hidden transition-all cursor-pointer bg-white border-slate-100 hover:border-indigo-200`}
                onClick={() => navigate(`/events/${event.id}`)}
              >
                <div className="h-48 w-full bg-gradient-to-b from-indigo-100 to-slate-100 flex items-center justify-center text-xs text-slate-400">
                  Event Image
                </div>
                <div className="p-3">
                  <h3 className="font-medium text-slate-800 line-clamp-2">{event.name || event.title || `Event #${event.id}`}</h3>
                  <p className="text-xs text-slate-500 line-clamp-1 mt-1">
                    {event.description || 'No description available'}
                  </p>
                </div>
              </div>
            );
          }) : (
            <div className="col-span-full p-8 text-center text-slate-500">
              <p>No events found in this category.</p>
              <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">Back to All Events</Link>
            </div>
          )}
        </div>

        {/* Fallback when no category selected */}
        {selectedCategory === null && events.length > 0 && (
          <div className="mb-6 text-center">
            <p className="text-slate-500">
              View events by <Link to="/events/category/1" className="font-medium text-indigo-600 hover:text-indigo-800">category</Link> or see <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">all events</Link>.
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

export default CategoryBrowsePage;
