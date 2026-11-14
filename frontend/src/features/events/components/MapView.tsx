import React, { useState, useEffect, useRef } from 'react';
import type { Event } from '../types/shared';

interface MapViewProps {
  events: Event[];
  onEventClick?: (event: Event) => void;
  className?: string;
}

const MapView = ({ events, onEventClick, className = '' }: MapViewProps) => {
  const mapRef = useRef<HTMLDivElement>(null);
  const [map, setMap] = useState<unknown>(null);
  const [markers, setMarkers] = useState<unknown[]>([]);

  // Dynamic import for map library (leaflet or mapbox)
  useEffect(() => {
    if (!mapRef.current || map) return;

    // Check if we have map coordinates for events
    const eventsWithCoords = events.filter(
      (e) => (e as any).venue_latitude && (e as any).venue_longitude
    );

    if (eventsWithCoords.length === 0) return;

    // For now, we'll show a placeholder since map library would need to be installed
    // In production, you would dynamically import leaflet or mapbox-gl here
    console.log('MapView: Would initialize map with', eventsWithCoords.length, 'events');
  }, [events, map]);

  const eventsWithCoords = events.filter(
    (e) => (e as any).venue_latitude && (e as any).venue_longitude
  );

  if (eventsWithCoords.length === 0) {
    return (
      <div className={`map-view ${className}`} style={{ minHeight: '400px' }}>
        <div className="map-view__empty">
          <svg
            className="map-view__empty-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="16" x2="12" y2="12" />
            <line x1="12" y1="8" x2="12" y2="8.01" />
          </svg>
          <h3 className="map-view__empty-title">No location data</h3>
          <p className="map-view__empty-description">
            Events don't have venue coordinates to display on the map.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className={`map-view ${className}`} style={{ minHeight: '400px' }}>
      <div ref={mapRef} className="map-view__container" />
      <div className="map-view__legend">
        <h4>Event Locations</h4>
        <ul>
          {eventsWithCoords.slice(0, 10).map((event) => (
            <li key={event.id} onClick={() => onEventClick?.(event)} style={{ cursor: 'pointer' }}>
              <strong>{event.title}</strong>
              <span>{event.venue_name || event.venue || 'Venue TBA'}</span>
            </li>
          ))}
          {eventsWithCoords.length > 10 && (
            <li>+{eventsWithCoords.length - 10} more events</li>
          )}
        </ul>
      </div>
    </div>
  );
};

export default React.memo(MapView);