# OrganizerDashboardPage

Route: /dashboard

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Central command hub displaying real-time aggregated metrics across all organizer's events. Summary cards show total revenue (all events combined), total tickets sold, average conversion rate, and upcoming event count. Interactive event cards display each event's key metrics: ticket sales progress bar, revenue to date, attendee count, and status badge (draft/upcoming/live/past). Quick-action buttons on each event card link to edit event, view detailed analytics, or manage inventory. Real-time sales activity feed shows latest ticket purchases across all events with timestamp, tier, and price. Filters allow viewing by event status (all/upcoming/past/draft) and date range. 'Create New Event' button prominently positioned for quick access. Auto-refresh every 30 seconds with live indicator badge showing 'Live' with pulse animation.
DESIGN APPROACH: Dense scannable layout optimized for power users. Summary metric cards at top (revenue, tickets sold, conversion rate, event count) with color-coded trend indicators (green=up, red=down, gray=flat). Event cards arranged in a responsive grid below metrics, each showing thumbnail, title, date, and progress bars. Sales activity feed as collapsible section at bottom. Sticky header with filter bar (status filter, date range picker). Mobile: stacked single-column with collapsible sections and full-width cards.
STATES: Loading shows skeleton metric cards and placeholder event cards with shimmer effect. Empty state (no events) shows 'Create your first event' illustration with 'Create Event' CTA button. Error 401 redirects to login with 'Session expired' toast. Error 403 shows 'Access Denied — only organizers can view dashboard' with back button. Error 500 shows error banner with retry button. Success displays full dashboard with live-updating metrics and event cards.
SECURITY: Organizer-only (requires 'Organizer' role via Policy). Auth required (bearer token). Rate limit 20/min per user for read operations to prevent abuse. Only shows organizer's own events and aggregated metrics.

## Additional Details

Flow Name: Ticket Inventory & Sales Management
Tech Stack Used:
  1. React Router
  2. TanStack Query
  3. Zustand
Visual Description: The Event Organizer Dashboard presents a modern and intuitive interface designed for efficient event management. The layout is structured into a two-column format with a sidebar on the left and a main content area on the right. The sidebar, with a width of approximately 250px, features a soft gradient background (#FF6B6B to #6B6BFF) and includes navigation links such as Dashboard, My Events, Ticket Management, and more. Each link is highlighted with an icon and text, using a bold 16px font for clarity. The sidebar also includes a prominent 'Create Event' button, styled with a gradient fill and rounded corners (8px radius), providing a clear call-to-action. The main content area begins with a top header that includes a search bar (styled with a subtle border and 8px border radius), a profile dropdown on the right, and a greeting section. The greeting section features a large, bold 'Good morning, Alex!' text (24px) with a friendly wave emoji, followed by a smaller, muted subtitle (italic 12px) encouraging the user to launch their next event. Below the greeting, four statistic cards display key metrics such as Total Ticket Sales and Total Revenue. Each card is a white rectangle with rounded corners (12px radius), subtle shadows, and uses a combination of bold and regular fonts to highlight numbers and labels. The cards are evenly spaced with a 16px gap. A line graph follows, showing recent event performance with a smooth orange line representing trends over time. The graph is enclosed in a card-like container with a subtle shadow and padding of 24px. The bottom section lists upcoming events in a table format. The table includes columns for Event Name, Date, Tickets Sold, Revenue, and Status. Each row is clearly delineated with a light border color (#E0E0E0) and uses a combination of bold and regular text to differentiate headings from data. The 'Status' column uses color-coded badges (pill-shaped) to indicate event status, such as 'Active' in green and 'Draft' in orange. Overall, the dashboard employs a clean, minimalistic design with a focus on usability and readability, using whitespace effectively to separate sections and guide the user's attention.
Related Screenshot: screenshot_12
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Referenced Endpoints:
  1. /api/organizer/events
  2. /api/organizer/events/:eventId/analytics/summary
  3. /api/organizer/events/:eventId/inventory/summary
Screenshot Analysis: {
  "matchReason": "Screenshot is an organizer dashboard and page is a dashboard route.",
  "pageType": "dashboard",
  "matchConfidence": 0.8,
  "layoutPattern": "two-column"
}
Visual Design: {
  "matchConfidence": 0.8,
  "layout": "two-column",
  "screenshotName": "Event Organizer Dashboard Overview",
  "borderRadius": {
    "badges": "9999px",
    "cards": "12px",
    "images": "50%",
    "inputs": "8px",
    "buttons": "8px"
  },
  "interactiveElements": [
    {
      "style": "primary",
      "label": "Create New Event",
      "type": "button",
      "action": "opens event creation form"
    },
    {
      "style": "secondary",
      "label": "View All Events",
      "type": "link",
      "action": "navigates to events page"
    },
    {
      "label": "Search bar",
      "style": "ghost",
      "type": "input",
      "action": "searches events"
    }
  ],
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692966166-224820.png?alt=media&token=d6f69c29-7b0c-4dee-9f13-61cfdaf5be13",
  "pageTypes": [
    "dashboard"
  ],
  "matchReason": "Screenshot is an organizer dashboard and page is a dashboard route.",
  "asciiDiagram": "\n+------------------------------------------+\n|  [=] EventFlow          Search...    [Profile] |\n+------------------------------------------+\n| [Dashboard]                              |\n| [My Events]                              |\n| [Ticket Management]                      |\n| [Attendee Management]                    |\n| [Event Analytics]                        |\n| [Marketing Tools]                        |\n| [Profile Settings]                       |\n|                                          |\n|  +------------------------------------+  |\n|  | Good morning, Alex!                |  |\n|  | Ready to launch your next big event?|  |\n|  +------------------------------------+  |\n|  | [Card] [Card] [Card] [Card]        |  |\n|  +------------------------------------+  |\n|  | Recent Event Performance           |  |\n|  | [Graph]                            |  |\n|  +------------------------------------+  |\n|  | Your Events                        |  |\n|  | [Table Row]                        |  |\n|  | [Table Row]                        |  |\n|  +------------------------------------+  |\n|                                          |\n+------------------------------------------+\n",
  "components": [
    "sidebar navigation",
    "top search bar",
    "profile dropdown",
    "greeting section",
    "statistic cards",
    "line graph",
    "event list table",
    "create event button"
  ],
  "screenshotId": "screenshot_12",
  "typography": {
    "specialStyles": [
      "italic 12px for muted text"
    ],
    "bodyStyle": "16px",
    "headingStyle": "bold 24px",
    "buttonStyle": "bold 14px"
  },
  "colors": {
    "text": "#333333",
    "background": "#F7F8FB",
    "accent": "#FF6B6B",
    "secondary": "#6B6BFF",
    "primary": "#FF6B6B"
  },
  "visualDescription": "The Event Organizer Dashboard presents a modern and intuitive interface designed for efficient event management. The layout is structured into a two-column format with a sidebar on the left and a main content area on the right. The sidebar, with a width of approximately 250px, features a soft gradient background (#FF6B6B to #6B6BFF) and includes navigation links such as Dashboard, My Events, Ticket Management, and more. Each link is highlighted with an icon and text, using a bold 16px font for clarity. The sidebar also includes a prominent 'Create Event' button, styled with a gradient fill and rounded corners (8px radius), providing a clear call-to-action. The main content area begins with a top header that includes a search bar (styled with a subtle border and 8px border radius), a profile dropdown on the right, and a greeting section. The greeting section features a large, bold 'Good morning, Alex!' text (24px) with a friendly wave emoji, followed by a smaller, muted subtitle (italic 12px) encouraging the user to launch their next event. Below the greeting, four statistic cards display key metrics such as Total Ticket Sales and Total Revenue. Each card is a white rectangle with rounded corners (12px radius), subtle shadows, and uses a combination of bold and regular fonts to highlight numbers and labels. The cards are evenly spaced with a 16px gap. A line graph follows, showing recent event performance with a smooth orange line representing trends over time. The graph is enclosed in a card-like container with a subtle shadow and padding of 24px. The bottom section lists upcoming events in a table format. The table includes columns for Event Name, Date, Tickets Sold, Revenue, and Status. Each row is clearly delineated with a light border color (#E0E0E0) and uses a combination of bold and regular text to differentiate headings from data. The 'Status' column uses color-coded badges (pill-shaped) to indicate event status, such as 'Active' in green and 'Draft' in orange. Overall, the dashboard employs a clean, minimalistic design with a focus on usability and readability, using whitespace effectively to separate sections and guide the user's attention.",
  "containsMultiplePages": false,
  "spacing": {
    "buttonPaddingX": "24px",
    "elementGap": "16px",
    "buttonPaddingY": "12px",
    "inputPadding": "12px 16px",
    "cardPadding": "24px",
    "sectionGap": "32px",
    "containerMargin": "16px"
  },
  "shadows": {
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "dropdowns": "0 1px 3px rgba(0,0,0,0.1)",
    "intensity": "minimal",
    "buttons": "0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)"
  }
}
Referenced Schemas:
  1. events
  2. analytics_events_metrics
  3. analytics_sales_timeline
  4. ticket_inventory

---
Generated by VisualPRD