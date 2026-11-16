# UI Components Documentation

## Overview
This document describes all UI components, their props, states, and edge cases.

## 1. EventCard

### Props
| Prop | Type | Description |
|------|------|-------------|
| `event` | `Event` | Required: event data object |
| `onClick` | `() => void` | Optional: click handler |
| `loading` | `boolean` | Optional: shows skeleton when true |
| `onFavoriteToggle` | `() => void` | Optional: favorite toggle handler |
| `onViewProfile` | `() => void` | Optional: organizer profile view |

### Required Fields Rendered
- Image (with Unsplash fallback)
- Title (truncated at 40 chars with tooltip)
- Organizer (shows "Unknown Organizer" if missing)
- Date (formatted relative time)
- Venue
- Price (with currency symbol)
- Availability badge (green/amber/red with patterns for color-blind support)

### Edge Cases Handled
- **Long titles**: Truncated with `title` attribute for full text on hover
- **Missing images**: Falls back to Unsplash placeholder
- **No organizer**: Shows "Unknown Organizer" text
- **Sold out**: Shows "Sold Out" text, disables interactions
- **Zero tickets**: Shows "Sold Out" badge with red color

### Accessibility
- `role="button"` with `tabIndex={0}`
- Keyboard: Enter/Space activates onClick
- ARIA labels on all interactive elements
- Tooltips on truncated text (`title` attribute)
- Proper contrast with pattern overlays for color-blind users

### Example
```tsx
<EventCard
  event={mockEvent}
  onClick={() => navigate(`/events/${mockEvent.id}`)}
  onFavoriteToggle={() => toggleFavorite(mockEvent.id)}
/>
```

---

## 2. EventGrid

### Props
| Prop | Type | Description |
|------|------|-------------|
| `events` | `Event[]` | Array of events to display |
| `columns` | `number` | Optional: grid columns (default: responsive) |
| `loading` | `boolean` | Optional: shows skeleton cards |
| `onLoadMore` | `() => void` | Optional: infinite scroll callback |
| `filterParams` | `object` | Optional: current filter state |
| `emptyStateComponent` | `ReactNode` | Optional: custom empty state |

### Responsive Grid
- **Desktop (>1024px)**: 3 columns
- **Tablet (768-1024px)**: 2 columns
- **Mobile (<768px)**: 1 column

### States
- **Loading**: Shows 6 skeleton cards in grid pattern
- **Empty**: "No events found" message with CTA
- **Loaded**: Responsive grid with event cards

### Example
```tsx
<EventGrid
  events={events}
  onLoadMore={loadMore}
  columns={3}
  filterParams={filterState}
/>
```
