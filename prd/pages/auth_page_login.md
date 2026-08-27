# LoginPage

Route: /login

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: Email and password login form with show/hide password toggle. Email field with format validation on blur. Password field with minimum length validation. 'Remember me' checkbox for persistent sessions. Form submission with loading state. 'Forgot password?' link routes to /forgot-password. 'Create account' link routes to /register. On successful login, auto-redirect to /dashboard. Inline field-level error messages for validation failures. Distinct error messaging for invalid credentials (401) vs network errors vs rate limiting.
DESIGN APPROACH: Centered card layout (max-w-md) on subtle gradient background. Minimal visual distractions — single focused action per screen. Password visibility toggle as icon button inside password field. Links positioned below form for secondary actions. Error messages appear inline below relevant field in red text, not in a separate banner.
STATES: Loading state disables all form fields and shows spinner in submit button. Validation error highlights field border in red with error message below. Auth error (401) shows 'Invalid email or password' below password field. Rate limit (429) shows 'Too many attempts, try again in X minutes' as toast. Network error shows dismissible toast with retry option. Success redirects immediately.
SECURITY: Public page (unauthenticated users only). Rate limit 5 attempts per 15 minutes per IP to prevent brute-force attacks. Authenticated users auto-redirect to /dashboard. Password never logged or exposed in errors.

## Additional Details

Flow Name: User Authentication & Account Management
Visual Description: The login screen for the Event Ticketing platform is designed with a modern and minimalistic style, featuring a clean and organized layout. The header section includes a logo on the left, which is a square icon with an orange background (#FF5A1F) and a white grid pattern. To the right of the logo, a 'SECURE' badge is displayed in a light green (#00C48C), indicating a secure connection. The main content area is centered and features a card with a white background (#FFFFFF) and subtle shadow for depth. The card begins with a bold heading 'Staff Login' in a large font size (24px), followed by a subheading in a regular font size (16px) that provides instructions for login. Below the text, two input fields are stacked vertically, each with a label: 'Email or Staff ID' and 'Password'. The input fields have a border radius of 8px and are lightly shadowed. A 'Forgot Password?' link in orange (#FF5A1F) is aligned to the right of the password field. A checkbox option labeled 'Keep me logged in for 30 days' is positioned below the inputs. The primary action button, 'Sign In to Dashboard', is prominently displayed in orange (#FF5A1F) with white text, featuring a medium shadow and rounded corners (8px). Below the button, a note about two-factor authentication is displayed with an icon. The footer contains links to 'System Status', 'Privacy Policy', 'Help Center', and 'Terms of Service', all in muted text color (#7A7A7A). At the bottom, a trust badge with a circular icon and text 'Trusted by event pros globally' is centered. The overall design uses ample whitespace to create a breathable and user-friendly interface.
Visual Design: {
  "layout": "single-column",
  "asciiDiagram": "+------------------------------------------+\n| [Logo] EVENTFLOW         [SECURE Badge] |\n+------------------------------------------+\n|                                          |\n|               Staff Login                |\n|  Welcome back. Enter your credentials    |\n|  to manage tickets and events.           |\n|                                          |\n|  [Email or Staff ID]                     |\n|  [Password]          [Forgot Password?]  |\n|  [ ] Keep me logged in for 30 days       |\n|                                          |\n|  [Sign In to Dashboard]                  |\n|                                          |\n|  Two-factor authentication (2FA) will    |\n|  be required on the next step for        |\n|  enhanced account security.              |\n|                                          |\n+------------------------------------------+\n| © 2024 EventFlow Technologies Inc.       |\n| SYSTEM STATUS | PRIVACY POLICY | HELP    |\n| CENTER | TERMS OF SERVICE                |\n|                                          |\n| [Trust Badge] Trusted by event pros      |\n| globally                                 |\n+------------------------------------------+",
  "borderRadius": {
    "inputs": "8px",
    "images": "50%",
    "cards": "12px",
    "badges": "9999px",
    "buttons": "8px"
  },
  "visualDescription": "The login screen for the Event Ticketing platform is designed with a modern and minimalistic style, featuring a clean and organized layout. The header section includes a logo on the left, which is a square icon with an orange background (#FF5A1F) and a white grid pattern. To the right of the logo, a 'SECURE' badge is displayed in a light green (#00C48C), indicating a secure connection. The main content area is centered and features a card with a white background (#FFFFFF) and subtle shadow for depth. The card begins with a bold heading 'Staff Login' in a large font size (24px), followed by a subheading in a regular font size (16px) that provides instructions for login. Below the text, two input fields are stacked vertically, each with a label: 'Email or Staff ID' and 'Password'. The input fields have a border radius of 8px and are lightly shadowed. A 'Forgot Password?' link in orange (#FF5A1F) is aligned to the right of the password field. A checkbox option labeled 'Keep me logged in for 30 days' is positioned below the inputs. The primary action button, 'Sign In to Dashboard', is prominently displayed in orange (#FF5A1F) with white text, featuring a medium shadow and rounded corners (8px). Below the button, a note about two-factor authentication is displayed with an icon. The footer contains links to 'System Status', 'Privacy Policy', 'Help Center', and 'Terms of Service', all in muted text color (#7A7A7A). At the bottom, a trust badge with a circular icon and text 'Trusted by event pros globally' is centered. The overall design uses ample whitespace to create a breathable and user-friendly interface.",
  "matchConfidence": 0.9,
  "shadows": {
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)",
    "intensity": "moderate",
    "dropdowns": "none"
  },
  "screenshotId": "screenshot_1",
  "spacing": {
    "elementGap": "16px",
    "containerMargin": "16px",
    "inputPadding": "12px 16px",
    "sectionGap": "32px",
    "buttonPaddingX": "24px",
    "buttonPaddingY": "12px",
    "cardPadding": "24px"
  },
  "pageTypes": [
    "login"
  ],
  "typography": {
    "buttonStyle": "bold 18px",
    "specialStyles": [
      "italic 14px"
    ],
    "headingStyle": "bold 24px",
    "bodyStyle": "16px"
  },
  "containsMultiplePages": false,
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692891333-224884.png?alt=media&token=c3562cde-6b50-4b5b-9279-e0a40af7dc41",
  "components": [
    "logo",
    "secure badge",
    "heading",
    "subheading",
    "input fields",
    "checkbox",
    "primary button",
    "link",
    "text",
    "footer links",
    "trust badge"
  ],
  "interactiveElements": [
    {
      "action": "submit login form",
      "label": "Sign In to Dashboard",
      "type": "button",
      "style": "primary"
    },
    {
      "style": "secondary",
      "type": "link",
      "action": "navigate to password recovery",
      "label": "Forgot Password?"
    },
    {
      "label": "Keep me logged in for 30 days",
      "action": "toggle persistent login",
      "type": "checkbox",
      "style": "default"
    }
  ],
  "matchReason": "Screenshot is a login screen and page is a login route.",
  "colors": {
    "background": "#FFF7F5",
    "primary": "#FF5A1F",
    "secondary": "#00C48C",
    "accent": "#FF5A1F",
    "text": "#1A1A1A"
  },
  "screenshotName": "Staff Login Screen"
}
Related Screenshot: screenshot_1
Referenced Schemas:
  1. users
Tech Stack Used:
  1. react-router-dom
  2. axios
Screenshot Analysis: {
  "pageType": "login",
  "layoutPattern": "single-column",
  "matchConfidence": 0.9,
  "matchReason": "Screenshot is a login screen and page is a login route."
}
Referenced Endpoints:
  1. /api/auth/login
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}

---
Generated by VisualPRD