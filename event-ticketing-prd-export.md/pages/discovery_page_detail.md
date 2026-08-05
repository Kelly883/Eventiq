# EventDetailPage

Route: /events/:eventId

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Comprehensive event detail view showing all information needed for purchase decision. Hero section with large banner image, event title, organizer info (avatar, name, link to public profile), date/time, venue address with map embed, event capacity and current attendance. Rich-text description with images. Ticket tier breakdown showing each tier (Regular, VIP, VVIP, Early Bird) with current price, quantity available, and 'Select Ticket' button. Active pricing window information with countdown timer to next price change. Reviews/ratings section (if implemented). Related events from same organizer. Share buttons for social media. 'Add to Wishlist' button (if authenticated). Sticky 'Buy Tickets' CTA button at bottom that opens checkout flow.
DESIGN APPROACH: Single-column scrollable layout optimized for mobile-first. Hero banner at top (full width, 300px height on mobile). Event details in card sections below (About, Tickets, Organizer, Related Events). Ticket tier cards displayed as horizontal scrollable carousel on mobile, grid on desktop. Map embed responsive. Sticky footer with 'Buy Tickets' button always visible. Organizer branding colors applied to CTA buttons and accents.
STATES: Loading shows skeleton banner, placeholder text blocks, and shimmer ticket cards. Empty state (event not found or not published) shows 'Event not found' with link to browse page. Error 401 redirects to login modal with 'Sign in to purchase tickets' message. Error 403 shows 'Access Denied' (shouldn't occur on public page). Error 404 shows 'Event not found' with back button. Error 500 shows error banner with retry. Success displays full event details with live-updating ticket availability.
SECURITY: Public page (no auth required). Rate limit 30/min per IP. Only displays published events. Organizer contact info filtered based on privacy settings. Ticket purchase requires authentication.

## Additional Details

Visual Design: {
  "matchConfidence": 0.9,
  "layout": "two-column",
  "borderRadius": {
    "buttons": "8px",
    "inputs": "8px",
    "images": "8px",
    "cards": "12px",
    "badges": "9999px"
  },
  "interactiveElements": [
    {
      "action": "share event",
      "type": "button",
      "style": "primary",
      "label": "Invite Friends"
    },
    {
      "action": "save event",
      "type": "button",
      "label": "Save",
      "style": "ghost"
    },
    {
      "style": "primary",
      "label": "Checkout Now",
      "action": "proceed to checkout",
      "type": "button"
    },
    {
      "label": "Follow Organizer",
      "type": "link",
      "action": "follow event organizer"
    }
  ],
  "screenshotName": "Event Details and Ticket Selection",
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692979102-224763.png?alt=media&token=39c8af78-ab70-4288-b433-89c8472bb436",
  "pageTypes": [
    "event-details"
  ],
  "matchReason": "Screenshot is an event details screen and page is an event details route.",
  "components": [
    "top navigation",
    "search bar",
    "hero image",
    "event information cards",
    "ticket selection panel",
    "event description",
    "image gallery",
    "location map",
    "footer"
  ],
  "asciiDiagram": "+------------------------------------------------+\n| [Logo]  [Search Bar]             [Profile Icon] |\n+------------------------------------------------+\n| +--------------------------------------------+ |\n| |                                            | |\n| |                HERO IMAGE                  | |\n| |                                            | |\n| +--------------------------------------------+ |\n| | Event Title                                | |\n| | Date | Time | Location                     | |\n| | [Save] [Invite Friends]                    | |\n+------------------------------------------------+\n| +-------------------+  +---------------------+ |\n| | Event Description |  | Ticket Selection    | |\n| | Image Gallery     |  | Panel               | |\n| +-------------------+  +---------------------+ |\n+------------------------------------------------+\n| Footer: Privacy Policy | Terms of Service | ... |\n+------------------------------------------------+",
  "screenshotId": "screenshot_14",
  "typography": {
    "specialStyles": [
      "italic for event name"
    ],
    "buttonStyle": "bold 18px",
    "bodyStyle": "16px",
    "headingStyle": "bold 24px"
  },
  "colors": {
    "secondary": "#FFFFFF",
    "primary": "#FF5722",
    "text": "#212529",
    "background": "#F8F9FA",
    "accent": "#FF5722"
  },
  "visualDescription": "The design presents a modern and clean interface with a focus on event details and ticket purchasing. The header features a logo on the left, a search bar in the center, and user profile icons on the right. Below, a hero image spans the width of the page, showcasing the event with a vibrant festival scene. Overlaying the image is the event title in bold, large typography, with details such as date, time, and location beneath it. To the right of the hero image, two prominent buttons allow users to save the event or invite friends, using a primary orange color for emphasis. The main content is divided into two sections: the left side contains event details, including a description and an image gallery, while the right side features a ticket selection panel. The ticket panel is styled with cards for each ticket type, displaying price and availability, and includes interactive elements for quantity adjustment. The footer provides links to privacy policy, terms of service, help center, and contact information. The overall color scheme is a combination of soft white and gray tones, accented by a vibrant orange for interactive elements, creating a visually appealing and user-friendly experience.",
  "containsMultiplePages": false,
  "spacing": {
    "containerMargin": "16px",
    "sectionGap": "32px",
    "buttonPaddingY": "12px",
    "inputPadding": "12px 16px",
    "cardPadding": "24px",
    "elementGap": "16px",
    "buttonPaddingX": "24px"
  },
  "shadows": {
    "dropdowns": "none",
    "intensity": "moderate",
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)"
  }
}
Referenced Schemas:
  1. events
  2. ticket_tiers
  3. pricing_windows
  4. organizers
  5. analytics_events_metrics
Screenshot Analysis: {
  "layoutPattern": "two-column",
  "matchReason": "Screenshot is an event details screen and page is an event details route.",
  "pageType": "event-details",
  "matchConfidence": 0.9
}
Referenced Endpoints:
  1. /api/events/public/:eventId
  2. /api/events/public/:eventId/pricing
  3. /api/events/public/:eventId/related
Visual Description: The design presents a modern and clean interface with a focus on event details and ticket purchasing. The header features a logo on the left, a search bar in the center, and user profile icons on the right. Below, a hero image spans the width of the page, showcasing the event with a vibrant festival scene. Overlaying the image is the event title in bold, large typography, with details such as date, time, and location beneath it. To the right of the hero image, two prominent buttons allow users to save the event or invite friends, using a primary orange color for emphasis. The main content is divided into two sections: the left side contains event details, including a description and an image gallery, while the right side features a ticket selection panel. The ticket panel is styled with cards for each ticket type, displaying price and availability, and includes interactive elements for quantity adjustment. The footer provides links to privacy policy, terms of service, help center, and contact information. The overall color scheme is a combination of soft white and gray tones, accented by a vibrant orange for interactive elements, creating a visually appealing and user-friendly experience.
Related Screenshot: screenshot_14
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Tech Stack Used:
  1. React Router
  2. TanStack Query
  3. Zustand
Flow Name: Event Discovery & Browsing

---
Generated by VisualPRD