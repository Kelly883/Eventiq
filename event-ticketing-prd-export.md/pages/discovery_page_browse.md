# EventBrowsePage

Route: /events

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Searchable, filterable event marketplace displaying all published events. Search bar with real-time autocomplete by event title. Filter sidebar with category dropdown, date range picker (upcoming/this week/this month/custom), price range slider, and location/venue search. Event cards display thumbnail image, title, organizer name with avatar, date/time, venue, ticket availability status (X tickets remaining or sold out), starting price, and social proof badge (trending/popular based on sales velocity). Sort options: relevance, date, price, popularity. Infinite scroll or pagination. Clicking an event card navigates to /events/:eventId for detailed view. 'Create Event' button visible only to authenticated organizers (routes to /organizer/events/create). Real-time inventory updates every 30 seconds showing ticket availability changes.
DESIGN APPROACH: Visual-first marketplace layout optimized for discovery. Hero section at top with search bar and prominent filters. Event cards in responsive grid (1 col mobile, 2 col tablet, 3 col desktop) with large thumbnail images. Filter sidebar sticky on desktop, collapsible drawer on mobile. Color-coded availability badges (green=plenty, amber=low, red=sold out). Organizer branding colors applied to event cards for visual consistency. Social proof indicators (trending badge, sales count) increase conversion.
STATES: Loading shows skeleton event cards (6 cards with shimmer effect) while fetching. Empty state (no events match filters) shows illustration with 'No events found' and 'Clear filters' button. Error 401 redirects to login modal with 'Sign in to browse events' message. Error 500 shows error banner with retry button. Success displays full event grid with live-updating availability badges.
SECURITY: Public page (no auth required). Rate limit 30/min per IP to prevent scraping. Only displays published events. Sensitive organizer data (email, phone) filtered based on organizer's privacy settings.

## Additional Details

Referenced Schemas:
  1. events
  2. ticket_tiers
  3. organizers
  4. analytics_events_metrics
Visual Design: {
  "matchConfidence": 0.8,
  "layout": "two-column",
  "pageTypes": [
    "homepage"
  ],
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773693022875-224691.png?alt=media&token=be7e4b63-e8fa-4436-b7ba-60506f98257f",
  "borderRadius": {
    "cards": "12px",
    "inputs": "8px",
    "buttons": "8px",
    "images": "8px",
    "badges": "12px"
  },
  "interactiveElements": [
    {
      "action": "navigate to event details",
      "type": "button",
      "label": "View Event",
      "style": "primary"
    },
    {
      "label": "Grab Seats",
      "style": "secondary",
      "action": "navigate to booking",
      "type": "button"
    },
    {
      "action": "subscribe",
      "type": "input",
      "style": "primary",
      "label": "Email"
    }
  ],
  "screenshotName": "Event Ticketing Homepage",
  "screenshotId": "screenshot_16",
  "asciiDiagram": "+------------------------------------------+\n|  [=] LOGO          Search...    [User]   |\n+------------------------------------------+\n| +--------------------------------------+ |\n| | Unforgettable moments wait for you.  | |\n| | Discover the most exclusive events...| |\n| +--------------------------------------+ |\n| [Refine Search] [Location] [Price] [Category] |\n+------------------------------------------+\n| Trending Now                             |\n| +----------------+ +----------------+ +----------------+ |\n| | Event Image    | | Event Image    | | Event Image    | |\n| | Event Details  | | Event Details  | | Event Details  | |\n| +----------------+ +----------------+ +----------------+ |\n| Browse by Category                       |\n| [Classical] [Food & Drink] [Sports] [Arts] [Wellness] [Theater] |\n+------------------------------------------+\n| Upcoming Events Near You                 |\n| +----------------+ +----------------+    |\n| | Event Image    | | Event Image    |    |\n| | Event Details  | | Event Details  |    |\n| +----------------+ +----------------+    |\n+------------------------------------------+\n| Footer                                    |\n| [Company] [Support] [Subscribe]           |\n+------------------------------------------+",
  "components": [
    "top navigation bar",
    "search input",
    "hero section",
    "filter buttons",
    "event cards",
    "category buttons",
    "upcoming events list",
    "footer"
  ],
  "matchReason": "Screenshot is an event ticketing homepage and page is an events browsing route.",
  "colors": {
    "text": "#212529",
    "accent": "#FF6F61",
    "background": "#F8F9FA",
    "secondary": "#FF9F43",
    "primary": "#FF6F61"
  },
  "typography": {
    "specialStyles": [
      "italic 12px"
    ],
    "headingStyle": "bold 24px",
    "buttonStyle": "bold 14px",
    "bodyStyle": "16px"
  },
  "visualDescription": "The Event Ticketing Homepage presents a modern and vibrant design aimed at engaging users immediately. The header features a logo on the left, a central search bar with placeholder text 'Search for concerts, tech summits, or art galleries...', and a user icon on the right. The hero section below is a large, colorful banner with a gradient background from pink (#FF6F61) to orange (#FF9F43), featuring bold white text 'Unforgettable moments wait for you.' and a brief description. Below the hero, a set of filter buttons allows users to refine their search by location, price, and category. The 'Trending Now' section showcases three event cards in a horizontal layout, each with an image, event title, date, location, and a 'View Event' button. The cards have a subtle shadow (0 2px 4px rgba(0,0,0,0.1)) and rounded corners (12px). Following this, a 'Browse by Category' section displays six rounded buttons, each representing a different category with icons and text. The 'Upcoming Events Near You' section lists events in a vertical format with images on the left and details on the right, including a 'Grab Seats' button. The footer is dark blue (#212529) with white text links organized into columns for navigation, support, and subscription. The overall layout is spacious, with consistent padding and margins, creating a clean and accessible user experience.",
  "shadows": {
    "buttons": "none",
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "intensity": "minimal",
    "dropdowns": "none"
  },
  "spacing": {
    "buttonPaddingX": "24px",
    "elementGap": "24px",
    "inputPadding": "12px 16px",
    "cardPadding": "16px",
    "buttonPaddingY": "12px",
    "sectionGap": "48px",
    "containerMargin": "16px"
  },
  "containsMultiplePages": false
}
Referenced Endpoints:
  1. /api/events/public/search
  2. /api/events/public/list
  3. /api/events/public/filters
Screenshot Analysis: {
  "layoutPattern": "two-column",
  "matchReason": "Screenshot is an event ticketing homepage and page is an events browsing route.",
  "pageType": "homepage",
  "matchConfidence": 0.8
}
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Related Screenshot: screenshot_16
Visual Description: The Event Ticketing Homepage presents a modern and vibrant design aimed at engaging users immediately. The header features a logo on the left, a central search bar with placeholder text 'Search for concerts, tech summits, or art galleries...', and a user icon on the right. The hero section below is a large, colorful banner with a gradient background from pink (#FF6F61) to orange (#FF9F43), featuring bold white text 'Unforgettable moments wait for you.' and a brief description. Below the hero, a set of filter buttons allows users to refine their search by location, price, and category. The 'Trending Now' section showcases three event cards in a horizontal layout, each with an image, event title, date, location, and a 'View Event' button. The cards have a subtle shadow (0 2px 4px rgba(0,0,0,0.1)) and rounded corners (12px). Following this, a 'Browse by Category' section displays six rounded buttons, each representing a different category with icons and text. The 'Upcoming Events Near You' section lists events in a vertical format with images on the left and details on the right, including a 'Grab Seats' button. The footer is dark blue (#212529) with white text links organized into columns for navigation, support, and subscription. The overall layout is spacious, with consistent padding and margins, creating a clean and accessible user experience.
Flow Name: Event Discovery & Browsing
Tech Stack Used:
  1. React Router
  2. TanStack Query
  3. Zustand

---
Generated by VisualPRD