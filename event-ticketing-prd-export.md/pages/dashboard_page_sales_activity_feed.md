# SalesActivityFeed

Route: /dashboard

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Real-time scrollable feed showing latest ticket sales across all organizer's events. Each activity item displays: event name, ticket tier, quantity sold, price, buyer email (if available), and timestamp. Color-coded by event for quick visual scanning. Filters allow viewing by event, tier, or date range. Auto-updates every 30 seconds with new sales appearing at top of feed. Clicking an activity item expands to show full transaction details. 'Export Activity' button downloads feed as CSV.
DESIGN APPROACH: Vertical scrollable list with compact activity items (one line per sale). Color-coded event badges on left. Timestamps relative (e.g., '2 minutes ago'). Collapsible section on dashboard that can be minimized. Mobile: full-width list items with horizontal scroll for details.
STATES: Loading shows skeleton activity items (5 items with shimmer). Empty state shows 'No sales yet' message. Success shows live-updating activity feed with new items appearing at top.

## Additional Details

Flow Name: Ticket Inventory & Sales Management
Tech Stack Used:
  1. React
  2. TanStack Query
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Visual Description: The Event Organizer Dashboard presents a modern and intuitive interface designed for efficient event management. The layout is structured into a two-column format with a sidebar on the left and a main content area on the right. The sidebar, with a width of approximately 250px, features a soft gradient background (#FF6B6B to #6B6BFF) and includes navigation links such as Dashboard, My Events, Ticket Management, and more. Each link is highlighted with an icon and text, using a bold 16px font for clarity. The sidebar also includes a prominent 'Create Event' button, styled with a gradient fill and rounded corners (8px radius), providing a clear call-to-action. The main content area begins with a top header that includes a search bar (styled with a subtle border and 8px border radius), a profile dropdown on the right, and a greeting section. The greeting section features a large, bold 'Good morning, Alex!' text (24px) with a friendly wave emoji, followed by a smaller, muted subtitle (italic 12px) encouraging the user to launch their next event. Below the greeting, four statistic cards display key metrics such as Total Ticket Sales and Total Revenue. Each card is a white rectangle with rounded corners (12px radius), subtle shadows, and uses a combination of bold and regular fonts to highlight numbers and labels. The cards are evenly spaced with a 16px gap. A line graph follows, showing recent event performance with a smooth orange line representing trends over time. The graph is enclosed in a card-like container with a subtle shadow and padding of 24px. The bottom section lists upcoming events in a table format. The table includes columns for Event Name, Date, Tickets Sold, Revenue, and Status. Each row is clearly delineated with a light border color (#E0E0E0) and uses a combination of bold and regular text to differentiate headings from data. The 'Status' column uses color-coded badges (pill-shaped) to indicate event status, such as 'Active' in green and 'Draft' in orange. Overall, the dashboard employs a clean, minimalistic design with a focus on usability and readability, using whitespace effectively to separate sections and guide the user's attention.
Related Screenshot: screenshot_12
Referenced Endpoints:
  1. /api/organizer/events/:eventId/analytics/sales-velocity
Screenshot Analysis: {
  "matchReason": "Screenshot is an organizer dashboard and page is a dashboard route.",
  "pageType": "dashboard",
  "matchConfidence": 0.8,
  "layoutPattern": "two-column"
}
Referenced Schemas:
  1. analytics_sales_timeline
  2. events
  3. ticket_tiers
Visual Design: {
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
  "asciiDiagram": "\n+------------------------------------------+\n|  [=] EventFlow          Search...    [Profile] |\n+------------------------------------------+\n| [Dashboard]                              |\n| [My Events]                              |\n| [Ticket Management]                      |\n| [Attendee Management]                    |\n| [Event Analytics]                        |\n| [Marketing Tools]                        |\n| [Profile Settings]                       |\n|                                          |\n|  +------------------------------------+  |\n|  | Good morning, Alex!                |  |\n|  | Ready to launch your next big event?|  |\n|  +------------------------------------+  |\n|  | [Card] [Card] [Card] [Card]        |  |\n|  +------------------------------------+  |\n|  | Recent Event Performance           |  |\n|  | [Graph]                            |  |\n|  +------------------------------------+  |\n|  | Your Events                        |  |\n|  | [Table Row]                        |  |\n|  | [Table Row]                        |  |\n|  +------------------------------------+  |\n|                                          |\n+------------------------------------------+\n",
  "matchReason": "Screenshot is an organizer dashboard and page is a dashboard route.",
  "screenshotId": "screenshot_12",
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692966166-224820.png?alt=media&token=d6f69c29-7b0c-4dee-9f13-61cfdaf5be13",
  "screenshotName": "Event Organizer Dashboard Overview",
  "interactiveElements": [
    {
      "action": "opens event creation form",
      "type": "button",
      "style": "primary",
      "label": "Create New Event"
    },
    {
      "label": "View All Events",
      "style": "secondary",
      "type": "link",
      "action": "navigates to events page"
    },
    {
      "type": "input",
      "action": "searches events",
      "label": "Search bar",
      "style": "ghost"
    }
  ],
  "borderRadius": {
    "badges": "9999px",
    "images": "50%",
    "inputs": "8px",
    "buttons": "8px",
    "cards": "12px"
  },
  "pageTypes": [
    "dashboard"
  ],
  "layout": "two-column",
  "matchConfidence": 0.8,
  "spacing": {
    "containerMargin": "16px",
    "sectionGap": "32px",
    "inputPadding": "12px 16px",
    "cardPadding": "24px",
    "buttonPaddingY": "12px",
    "elementGap": "16px",
    "buttonPaddingX": "24px"
  },
  "containsMultiplePages": false,
  "shadows": {
    "dropdowns": "0 1px 3px rgba(0,0,0,0.1)",
    "intensity": "minimal",
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)"
  },
  "visualDescription": "The Event Organizer Dashboard presents a modern and intuitive interface designed for efficient event management. The layout is structured into a two-column format with a sidebar on the left and a main content area on the right. The sidebar, with a width of approximately 250px, features a soft gradient background (#FF6B6B to #6B6BFF) and includes navigation links such as Dashboard, My Events, Ticket Management, and more. Each link is highlighted with an icon and text, using a bold 16px font for clarity. The sidebar also includes a prominent 'Create Event' button, styled with a gradient fill and rounded corners (8px radius), providing a clear call-to-action. The main content area begins with a top header that includes a search bar (styled with a subtle border and 8px border radius), a profile dropdown on the right, and a greeting section. The greeting section features a large, bold 'Good morning, Alex!' text (24px) with a friendly wave emoji, followed by a smaller, muted subtitle (italic 12px) encouraging the user to launch their next event. Below the greeting, four statistic cards display key metrics such as Total Ticket Sales and Total Revenue. Each card is a white rectangle with rounded corners (12px radius), subtle shadows, and uses a combination of bold and regular fonts to highlight numbers and labels. The cards are evenly spaced with a 16px gap. A line graph follows, showing recent event performance with a smooth orange line representing trends over time. The graph is enclosed in a card-like container with a subtle shadow and padding of 24px. The bottom section lists upcoming events in a table format. The table includes columns for Event Name, Date, Tickets Sold, Revenue, and Status. Each row is clearly delineated with a light border color (#E0E0E0) and uses a combination of bold and regular text to differentiate headings from data. The 'Status' column uses color-coded badges (pill-shaped) to indicate event status, such as 'Active' in green and 'Draft' in orange. Overall, the dashboard employs a clean, minimalistic design with a focus on usability and readability, using whitespace effectively to separate sections and guide the user's attention.",
  "typography": {
    "specialStyles": [
      "italic 12px for muted text"
    ],
    "buttonStyle": "bold 14px",
    "headingStyle": "bold 24px",
    "bodyStyle": "16px"
  },
  "colors": {
    "secondary": "#6B6BFF",
    "primary": "#FF6B6B",
    "accent": "#FF6B6B",
    "background": "#F7F8FB",
    "text": "#333333"
  }
}

---
Generated by VisualPRD