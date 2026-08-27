# VenueCheckInPage

Route: /venue/check-in/:eventId

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Venue staff interface for scanning QR codes at event entry. Large camera input area with real-time QR code scanning using a QR code reader SDK (qrcode.js or similar). When a QR code is scanned, the system decrypts the ticket data, validates it against the database, and displays ticket status (valid/checked-in/void). Shows attendee name, ticket tier, and check-in timestamp. Action buttons: 'Check In' (marks ticket as checked-in), 'Mark Void' (for fraudulent tickets), 'Manual Entry' (fallback for unreadable codes). Search bar for manual ticket lookup by ticket ID or attendee email. Real-time sync shows check-in count and remaining capacity. Offline mode caches recent tickets for venue staff without internet connectivity.
DESIGN APPROACH: Full-screen camera view optimized for mobile scanning. Large action buttons for quick venue staff interaction. Minimal distractions — focused on scanning and check-in action. Ticket details displayed in a card overlay on top of camera feed. Search bar sticky at top for fallback lookups. Color-coded status badges (green=valid, amber=already checked-in, red=void/invalid). Offline indicator badge shows sync status.
STATES: Loading shows 'Initializing camera...' with spinner. Camera permission denied shows 'Please enable camera access' with settings link. Invalid QR shows 'Invalid ticket' with red badge. Already checked-in shows 'Already checked in at HH:MM' with amber badge. Void ticket shows 'This ticket is invalid' with red badge. Network error shows 'Offline mode - limited functionality' with sync status. Success shows green checkmark and 'Checked in successfully' with attendee name.
SECURITY: Venue staff only (requires 'VenueStaff' or 'Organizer' role via Policy). Auth required (bearer token). Rate limit 60 check-ins/min per venue staff to prevent abuse. All check-ins logged to auditLogs with staff ID, timestamp, and ticket ID. Offline data synced when connection restored.

## Additional Details

Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Referenced Endpoints:
  1. /api/tickets/verify-qr
  2. /api/tickets/:ticketId/check-in
  3. /api/venue/check-in/sync
Screenshot Analysis: {
  "layoutPattern": "single-page",
  "matchConfidence": 0.8,
  "pageType": "scanner",
  "matchReason": "Screenshot is a QR code scanner and page is a venue check-in route, which involves scanning."
}
Tech Stack Used:
  1. qrcode
  2. jsQR
Referenced Schemas:
  1. tickets
  2. events
  3. fraud_events
Related Screenshot: screenshot_8
Visual Design: {
  "interactiveElements": [
    {
      "style": "primary",
      "type": "button",
      "label": "Manual Entry",
      "action": "Opens manual entry form"
    },
    {
      "type": "link",
      "style": "primary",
      "label": "Scanner",
      "action": "Navigate to scanner"
    },
    {
      "action": "Navigate to insights",
      "label": "Insights",
      "style": "secondary",
      "type": "link"
    },
    {
      "type": "link",
      "style": "secondary",
      "action": "Navigate to staff management",
      "label": "Staff"
    },
    {
      "action": "Navigate to settings",
      "label": "Settings",
      "type": "link",
      "style": "secondary"
    }
  ],
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692929172-224819.png?alt=media&token=bec5ec7c-5493-4e24-bcd5-2148682ae48c",
  "components": [
    "top navigation bar",
    "QR code scanning area",
    "live check-in stats",
    "manual entry button",
    "bottom navigation bar"
  ],
  "pageTypes": [
    "scanner"
  ],
  "typography": {
    "headingStyle": "bold 24px",
    "specialStyles": [
      "uppercase for labels"
    ],
    "bodyStyle": "16px",
    "buttonStyle": "16px bold"
  },
  "containsMultiplePages": false,
  "screenshotName": "QR Code Scanner Interface",
  "matchReason": "Screenshot is a QR code scanner and page is a venue check-in route, which involves scanning.",
  "colors": {
    "background": "#0A0E1A",
    "primary": "#FF007A",
    "secondary": "#FFFFFF",
    "accent": "#FF007A",
    "text": "#FFFFFF"
  },
  "screenshotId": "screenshot_8",
  "spacing": {
    "elementGap": "16px",
    "containerMargin": "16px",
    "buttonPaddingX": "24px",
    "sectionGap": "32px",
    "inputPadding": "12px 16px",
    "cardPadding": "24px",
    "buttonPaddingY": "12px"
  },
  "matchConfidence": 0.8,
  "shadows": {
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "none",
    "dropdowns": "none",
    "intensity": "minimal"
  },
  "asciiDiagram": "+------------------------------------------+\n|  [=] EventFlow Scanner          [icon]   |\n+------------------------------------------+\n|                                          |\n|  +------------------------------------+  |\n|  |                                    |  |\n|  |                                    |  |\n|  |        Align QR Code to scan       |  |\n|  |                                    |  |\n|  |                                    |  |\n|  +------------------------------------+  |\n|                                          |\n+------------------------------------------+\n|  LIVE CHECK-IN     1,248 / 2,500   [50%] |\n|  [Manual Entry]                          |\n+------------------------------------------+\n| [Scanner] [Insights] [Staff] [Settings]  |\n+------------------------------------------+",
  "layout": "single-page",
  "borderRadius": {
    "inputs": "8px",
    "buttons": "8px",
    "badges": "4px",
    "cards": "12px",
    "images": "8px"
  },
  "visualDescription": "The interface presents a modern and sleek design, with a focus on functionality and ease of use. At the top, a navigation bar features the app logo on the left and a profile icon on the right. The main section is dominated by a QR code scanning area, outlined with a gradient border transitioning from pink to orange. This area is centered and occupies a significant portion of the screen, with a placeholder text 'Align QR Code to scan' in the middle. Below, a card displays live check-in statistics, showing '1,248 / 2,500' attendees checked in, accompanied by a circular progress indicator on the right. The card has a dark background with white text, creating a strong contrast for readability. A 'Manual Entry' button is positioned below the stats, styled with a yellow icon and text, indicating its primary action status. At the bottom, a navigation bar contains four icons labeled 'Scanner', 'Insights', 'Staff', and 'Settings', with the 'Scanner' icon highlighted in pink to indicate the current active page. The overall color scheme is dark, with navy and black tones, accented by vibrant pinks and yellows, creating a visually appealing and professional look. Typography is clean and modern, utilizing bold and uppercase styles for emphasis. Spacing is generous, providing a clear and uncluttered interface."
}
Visual Description: The interface presents a modern and sleek design, with a focus on functionality and ease of use. At the top, a navigation bar features the app logo on the left and a profile icon on the right. The main section is dominated by a QR code scanning area, outlined with a gradient border transitioning from pink to orange. This area is centered and occupies a significant portion of the screen, with a placeholder text 'Align QR Code to scan' in the middle. Below, a card displays live check-in statistics, showing '1,248 / 2,500' attendees checked in, accompanied by a circular progress indicator on the right. The card has a dark background with white text, creating a strong contrast for readability. A 'Manual Entry' button is positioned below the stats, styled with a yellow icon and text, indicating its primary action status. At the bottom, a navigation bar contains four icons labeled 'Scanner', 'Insights', 'Staff', and 'Settings', with the 'Scanner' icon highlighted in pink to indicate the current active page. The overall color scheme is dark, with navy and black tones, accented by vibrant pinks and yellows, creating a visually appealing and professional look. Typography is clean and modern, utilizing bold and uppercase styles for emphasis. Spacing is generous, providing a clear and uncluttered interface.
Flow Name: Ticket Delivery & User Dashboard

---
Generated by VisualPRD