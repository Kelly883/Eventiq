# AdminDashboardPage

Route: /admin/dashboard

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Centralized admin command center displaying real-time platform metrics and quick-access controls. Summary cards show total platform revenue (24h), active events, pending user approvals, flagged transactions count, and failed payouts. Interactive navigation tiles link to core admin functions: User Management, Event Moderation, Payment Reconciliation, Dispute Resolution, System Configuration. Real-time activity feed shows recent admin actions (user suspensions, event approvals, payout approvals) with timestamps and admin names. Quick-stats section displays platform health: uptime percentage, API response times, database query performance. Alerts section highlights critical issues requiring immediate attention (failed payment batches, high fraud scores, system errors). Search bar for quick lookup of users, events, or orders by ID or email. Filters by date range and alert severity. Auto-refresh every 60 seconds with visual indicators showing last update time.
DESIGN APPROACH: Dense scannable layout optimized for power users. Metric cards at top with color-coded trend indicators (green=up, red=down). Navigation tiles arranged in 2x3 grid below metrics for quick access. Activity feed and alerts as collapsible sections. Sticky header with search and date range picker. Mobile: stacked single-column with collapsible sections and full-width tiles.
STATES: Loading shows skeleton metric cards and placeholder activity items with shimmer effect. Empty state (no recent activity) shows 'No recent activity' message. Error 401 redirects to login with 'Session expired' toast. Error 403 shows 'Access Denied — only admins can view dashboard' with back button. Error 500 shows error banner with retry button. Success displays full dashboard with live-updating metrics and activity feed.
SECURITY: Admin-only (requires 'Admin' role via Policy). Auth required (bearer token). Rate limit 20/min per user for read operations to prevent abuse. All dashboard views logged to auditLogs with admin ID and timestamp.

## Additional Details

Tech Stack Used:
  1. React Router
  2. TanStack Query
Referenced Schemas:
  1. users
  2. orders
  3. events
  4. payouts
  5. fraud_events
  6. auditLogs
Visual Design: {
  "interactiveElements": [
    {
      "label": "Create Report",
      "action": "opens report creation modal",
      "type": "button",
      "style": "primary"
    },
    {
      "style": "secondary",
      "type": "link",
      "label": "View All",
      "action": "navigates to full event approvals"
    },
    {
      "style": "ghost",
      "type": "input",
      "label": "Search platform data...",
      "action": "searches data"
    }
  ],
  "components": [
    "sidebar navigation",
    "search bar",
    "user avatar",
    "notification icon",
    "statistic cards",
    "event approvals table",
    "recent activity list",
    "user management overview map",
    "create report button"
  ],
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692910210-224888.png?alt=media&token=f6d1736b-6a16-4d5f-a3f3-5b06a7e9abe0",
  "pageTypes": [
    "dashboard"
  ],
  "containsMultiplePages": false,
  "typography": {
    "specialStyles": [
      "italic for muted text"
    ],
    "headingStyle": "bold 24px",
    "bodyStyle": "16px",
    "buttonStyle": "bold 14px"
  },
  "screenshotName": "Admin Dashboard Overview",
  "matchReason": "Screenshot is an admin dashboard and page is an admin dashboard route.",
  "colors": {
    "secondary": "#4ECDC4",
    "accent": "#FF6B6B",
    "text": "#333333",
    "background": "#F7F8FA",
    "primary": "#FF6B6B"
  },
  "screenshotId": "screenshot_6",
  "spacing": {
    "containerMargin": "16px",
    "elementGap": "16px",
    "inputPadding": "12px 16px",
    "sectionGap": "32px",
    "buttonPaddingX": "24px",
    "buttonPaddingY": "12px",
    "cardPadding": "24px"
  },
  "matchConfidence": 0.9,
  "shadows": {
    "buttons": "none",
    "dropdowns": "none",
    "intensity": "minimal",
    "cards": "0 1px 3px rgba(0,0,0,0.1)"
  },
  "borderRadius": {
    "inputs": "8px",
    "badges": "9999px",
    "buttons": "8px",
    "cards": "12px",
    "images": "50%"
  },
  "asciiDiagram": "+------------------------------------------+\n|  [=] EventFlow ADMIN CONSOLE             |\n+------------------------------------------+\n| [Dashboard] [Manage Events] [Settings]   |\n|                                          |\n| +--------------------------------------+ |\n| | Search platform data...              | |\n| +--------------------------------------+ |\n| +--------------------------------------+ |\n| | Total Revenue | Tickets Sold | Users | |\n| +--------------------------------------+ |\n| | Event Approvals                      | |\n| | [View All]                           | |\n| +--------------------------------------+ |\n| | Recent Activity                      | |\n| +--------------------------------------+ |\n| | User Management Overview             | |\n| +--------------------------------------+ |\n| | Create Report                        | |\n+------------------------------------------+",
  "layout": "two-column",
  "visualDescription": "The dashboard presents a modern and minimalistic design with a two-column layout. On the left, a sidebar navigation is present, featuring a logo at the top followed by menu items such as 'Dashboard', 'Manage Events', and 'Settings'. The sidebar is 250px wide with a light background (#F7F8FA) and active items highlighted with a primary color accent (#FF6B6B). The main content area is divided into sections with a header containing a search bar, user avatar, and notification icon. Below the header, three statistic cards display key metrics like 'Total Revenue' and 'Tickets Sold', each card measuring approximately 200px by 100px with a subtle shadow. The 'Event Approvals' section features a table with columns for event name, organizer, date, category, and status, with a 'View All' link for expanded details. Adjacent to this, a 'Recent Activity' list provides updates in a vertical card format. At the bottom, a 'User Management Overview' map visualizes global traffic. The 'Create Report' button, styled with a gradient, is prominently placed in the sidebar. Typography is clean, using a sans-serif font with bold headings (24px) and regular body text (16px). Spacing is consistent, with 32px between sections and 16px between elements. The color scheme is cohesive, using muted tones with vibrant accents for emphasis. Overall, the design is user-friendly, emphasizing clarity and accessibility."
}
Visual Description: The dashboard presents a modern and minimalistic design with a two-column layout. On the left, a sidebar navigation is present, featuring a logo at the top followed by menu items such as 'Dashboard', 'Manage Events', and 'Settings'. The sidebar is 250px wide with a light background (#F7F8FA) and active items highlighted with a primary color accent (#FF6B6B). The main content area is divided into sections with a header containing a search bar, user avatar, and notification icon. Below the header, three statistic cards display key metrics like 'Total Revenue' and 'Tickets Sold', each card measuring approximately 200px by 100px with a subtle shadow. The 'Event Approvals' section features a table with columns for event name, organizer, date, category, and status, with a 'View All' link for expanded details. Adjacent to this, a 'Recent Activity' list provides updates in a vertical card format. At the bottom, a 'User Management Overview' map visualizes global traffic. The 'Create Report' button, styled with a gradient, is prominently placed in the sidebar. Typography is clean, using a sans-serif font with bold headings (24px) and regular body text (16px). Spacing is consistent, with 32px between sections and 16px between elements. The color scheme is cohesive, using muted tones with vibrant accents for emphasis. Overall, the design is user-friendly, emphasizing clarity and accessibility.
Related Screenshot: screenshot_6
Flow Name: Admin Panel & Platform Management
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Referenced Endpoints:
  1. /api/admin/dashboard/overview
  2. /api/admin/dashboard/activity-feed
  3. /api/admin/dashboard/alerts
Screenshot Analysis: {
  "matchConfidence": 0.9,
  "layoutPattern": "two-column",
  "pageType": "dashboard",
  "matchReason": "Screenshot is an admin dashboard and page is an admin dashboard route."
}

---
Generated by VisualPRD