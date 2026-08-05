# EventCreatePage

Route: /organizer/events/create

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: A comprehensive form for creating a new event. Fields include event title, rich-text description, start/end date and time pickers, venue details (name, address), and total capacity. Features a dynamic section to add, edit, and remove multiple ticket tiers (e.g., Regular, VIP), each with its own name, price, and quantity. Includes a file upload component for the event's banner image. A 'Save as Draft' button creates the event with a 'draft' status, while 'Publish Event' creates it as 'published'.
DESIGN APPROACH: A single-page, long-scroll form broken into logical sections using cards (Details, Venue, Tickets, Media). The rich-text editor uses a library like TipTap or TinyMCE. The ticket tiers section allows for dynamically adding/removing form groups. The form uses real-time validation on blur. A sticky footer contains the 'Save' and 'Publish' actions.
STATES: The page itself loads instantly. 'Saving' state disables the form and shows a spinner on the clicked button. Success shows a toast notification ('Event created successfully!') and redirects to the event list page. Validation errors are displayed inline below each field.
SECURITY: Protected route for users with the 'organizer' role. All inputs are validated server-side.

## Additional Details

Referenced Schemas:
  1. events
  2. ticket_tiers
Visual Design: {
  "typography": {
    "specialStyles": [
      "italic 14px"
    ],
    "headingStyle": "bold 24px",
    "bodyStyle": "16px",
    "buttonStyle": "bold 16px"
  },
  "colors": {
    "background": "#F9F9F9",
    "accent": "#FF6B00",
    "text": "#333333",
    "secondary": "#FF9F57",
    "primary": "#FF6B00"
  },
  "spacing": {
    "containerMargin": "16px",
    "sectionGap": "32px",
    "cardPadding": "24px",
    "inputPadding": "12px 16px",
    "buttonPaddingY": "12px",
    "elementGap": "16px",
    "buttonPaddingX": "24px"
  },
  "containsMultiplePages": false,
  "shadows": {
    "intensity": "minimal",
    "dropdowns": "none",
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "none"
  },
  "visualDescription": "The Event Creation screen presents a clean, modern, and minimalistic design tailored for an intuitive user experience. The layout is structured in a single-column format, optimizing for vertical scrolling and clear information hierarchy. At the top, a header displays the title 'EventFlow Mobile Event Creation' aligned to the center, with a close icon on the left for easy exit. Below, a progress indicator visually represents the current step in the event creation process, with 'Basic Info' highlighted in orange, indicating the active stage. The form begins with a section titled 'Basic Information' in bold 24px font, followed by a muted subtitle that guides the user to input core event details. The form fields are neatly organized with generous spacing of 16px between each element. The 'Add Cover Image' section is prominently displayed with a dashed border and a camera icon, inviting users to upload an image. Input fields for 'Event Title', 'Category', 'Date', 'Time', 'Location', and 'Description' follow, each with clear labels and placeholders. The 'Category' field is a dropdown, while 'Date' and 'Time' fields incorporate icons for calendar and clock, respectively, indicating interactive elements. A map placeholder suggests location input functionality. At the bottom, a toggle switch labeled 'Public Event' allows users to set event visibility, with a description in muted text. The interface concludes with two buttons: 'Save Draft' in a secondary style and 'Next: Tickets' in a primary style, both with rounded corners and subtle shadows. The overall color scheme is warm and inviting, with primary accents in #FF6B00, complemented by a soft background of #F9F9F9. Text is predominantly dark gray (#333333), ensuring readability against the light background. The design employs subtle shadows and rounded corners to create a sense of depth and approachability, while maintaining a professional and polished appearance.",
  "layout": "single-column",
  "matchConfidence": 0.9,
  "asciiDiagram": "+------------------------------------------+\n| X EventFlow Mobile Event Creation        |\n+------------------------------------------+\n|  ● Basic Info   ○ Tickets   ○ Review     |\n+------------------------------------------+\n| Basic Information                        |\n| Let's start with the core details...     |\n+------------------------------------------+\n| [ Add Cover Image ]                      |\n+------------------------------------------+\n| Event Title: [____________________]      |\n+------------------------------------------+\n| Category: [ Select a category ▼ ]        |\n+------------------------------------------+\n| Date: [mm/dd/yyyy] [📅]                  |\n| Time: [--:-- --] [⏰]                    |\n+------------------------------------------+\n| Location: [ Search for a venue or address ] |\n+------------------------------------------+\n| [ Map Placeholder ]                      |\n+------------------------------------------+\n| Description:                             |\n| [ Tell people what makes your event... ] |\n+------------------------------------------+\n| [🔄] Public Event                         |\n| Discoverable by anyone on EventFlow      |\n+------------------------------------------+\n| [ Save Draft ]   [ Next: Tickets ► ]     |\n+------------------------------------------+",
  "components": [
    "header",
    "progress-indicator",
    "form",
    "buttons",
    "toggle",
    "icons",
    "input fields",
    "dropdown",
    "date picker",
    "time picker",
    "map placeholder"
  ],
  "matchReason": "Screenshot is an event creation screen and page is an event creation route.",
  "screenshotId": "screenshot_5",
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692905705-224823.png?alt=media&token=22d7a3b3-c0e4-4b0d-84fa-be8be451026b",
  "screenshotName": "Event Creation - Basic Info",
  "borderRadius": {
    "images": "8px",
    "inputs": "8px",
    "buttons": "8px",
    "cards": "12px",
    "badges": "9999px"
  },
  "interactiveElements": [
    {
      "action": "save current form state",
      "type": "button",
      "style": "secondary",
      "label": "Save Draft"
    },
    {
      "label": "Next: Tickets",
      "style": "primary",
      "type": "button",
      "action": "proceed to next step"
    },
    {
      "type": "toggle",
      "action": "toggle event visibility",
      "label": "Public Event",
      "style": "primary"
    },
    {
      "style": "standard",
      "label": "Event Title",
      "action": "enter event name",
      "type": "input"
    },
    {
      "label": "Category",
      "style": "standard",
      "action": "select event category",
      "type": "dropdown"
    },
    {
      "label": "Date",
      "style": "standard",
      "action": "select event date",
      "type": "input"
    },
    {
      "action": "select event time",
      "type": "input",
      "label": "Time",
      "style": "standard"
    },
    {
      "label": "Location",
      "style": "standard",
      "action": "enter event location",
      "type": "input"
    },
    {
      "style": "standard",
      "label": "Description",
      "type": "input",
      "action": "enter event description"
    }
  ],
  "pageTypes": [
    "event-creation"
  ]
}
Referenced Endpoints:
  1. /api/organizer/events
  2. /api/organizer/events/:eventId/upload-banner
Screenshot Analysis: {
  "pageType": "event-creation",
  "matchReason": "Screenshot is an event creation screen and page is an event creation route.",
  "matchConfidence": 0.9,
  "layoutPattern": "single-column"
}
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Visual Description: The Event Creation screen presents a clean, modern, and minimalistic design tailored for an intuitive user experience. The layout is structured in a single-column format, optimizing for vertical scrolling and clear information hierarchy. At the top, a header displays the title 'EventFlow Mobile Event Creation' aligned to the center, with a close icon on the left for easy exit. Below, a progress indicator visually represents the current step in the event creation process, with 'Basic Info' highlighted in orange, indicating the active stage. The form begins with a section titled 'Basic Information' in bold 24px font, followed by a muted subtitle that guides the user to input core event details. The form fields are neatly organized with generous spacing of 16px between each element. The 'Add Cover Image' section is prominently displayed with a dashed border and a camera icon, inviting users to upload an image. Input fields for 'Event Title', 'Category', 'Date', 'Time', 'Location', and 'Description' follow, each with clear labels and placeholders. The 'Category' field is a dropdown, while 'Date' and 'Time' fields incorporate icons for calendar and clock, respectively, indicating interactive elements. A map placeholder suggests location input functionality. At the bottom, a toggle switch labeled 'Public Event' allows users to set event visibility, with a description in muted text. The interface concludes with two buttons: 'Save Draft' in a secondary style and 'Next: Tickets' in a primary style, both with rounded corners and subtle shadows. The overall color scheme is warm and inviting, with primary accents in #FF6B00, complemented by a soft background of #F9F9F9. Text is predominantly dark gray (#333333), ensuring readability against the light background. The design employs subtle shadows and rounded corners to create a sense of depth and approachability, while maintaining a professional and polished appearance.
Related Screenshot: screenshot_5
Flow Name: Organizer Setup & Event Creation
Tech Stack Used:
  1. react-router
  2. react-hook-form
  3. swr
  4. tiptap

---
Generated by VisualPRD