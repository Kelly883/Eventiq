# CheckoutPage

Route: /checkout

> **Design System:** When implementing this page, ensure it follows the design system
> specifications in `design-system.md` for colors, typography, spacing, and component patterns.

## Description

FUNCTIONALITY: A multi-step checkout process. It first calls `/api/cart/verify` to confirm prices and availability. Step 1 displays a final order summary. Step 2 (for guest users or users without saved info) collects billing information. Step 3 integrates a payment provider's UI component (e.g., Stripe's `CardElement`) for secure payment info collection. This is initialized using a client secret from `/api/checkout/create-payment-intent`. On submission, it calls the payment provider's confirmation method (e.g., `stripe.confirmCardPayment`).
DESIGN APPROACH: A single-page, multi-step wizard layout. The right side (desktop) or a collapsible section (mobile) shows the order summary throughout the process. The main area transitions between steps. Payment provider elements are styled to match the site's theme. Uses React hooks like `useState` and `useEffect` to manage form state and API calls, and `useNavigate` for redirects.
STATES: Loading: A full-page overlay spinner while creating the payment intent. Submitting: Spinner on the 'Pay' button, form fields disabled. Error: Displays specific error messages from the payment provider (e.g., 'Your card was declined') or server ('One or more items are no longer available'). Success: Redirects to the order confirmation page `/order/{orderId}/confirmation`.
SECURITY: Requires authentication. Users are prompted to log in or register if they are not already. All payment data is handled by the payment provider's SDK, ensuring PCI compliance.

## Additional Details

Screenshot Analysis: {
  "matchReason": "Screenshot is a checkout screen and page is a checkout route.",
  "pageType": "checkout",
  "layoutPattern": "two-column",
  "matchConfidence": 0.9
}
Referenced Endpoints:
  1. /api/cart/verify
  2. /api/checkout/create-payment-intent
Updated At: {
  "type": "firestore/timestamp/1.0",
  "seconds": 1773700160,
  "nanoseconds": 688000000
}
Related Screenshot: screenshot_10
Visual Design: {
  "spacing": {
    "buttonPaddingY": "12px",
    "cardPadding": "24px",
    "containerMargin": "16px",
    "elementGap": "16px",
    "inputPadding": "12px 16px",
    "sectionGap": "32px",
    "buttonPaddingX": "24px"
  },
  "screenshotId": "screenshot_10",
  "matchReason": "Screenshot is a checkout screen and page is a checkout route.",
  "colors": {
    "secondary": "#ff7e00",
    "accent": "#ff5a5f",
    "text": "#333333",
    "background": "#f7f7f9",
    "primary": "#ff5a5f"
  },
  "screenshotName": "Checkout - Ticket Summary and Payment",
  "pageTypes": [
    "checkout"
  ],
  "containsMultiplePages": false,
  "typography": {
    "buttonStyle": "bold 16px",
    "headingStyle": "bold 24px",
    "specialStyles": [
      "italic for event name"
    ],
    "bodyStyle": "16px"
  },
  "interactiveElements": [
    {
      "style": "primary",
      "type": "button",
      "action": "proceed to payment",
      "label": "PAY NOW"
    },
    {
      "label": "Back to Event",
      "action": "navigate to event page",
      "style": "secondary",
      "type": "link"
    },
    {
      "style": "standard",
      "type": "input",
      "action": "enter full name",
      "label": "Full Name"
    },
    {
      "style": "standard",
      "type": "input",
      "action": "enter email",
      "label": "Email Address"
    },
    {
      "style": "standard",
      "type": "input",
      "label": "Phone Number",
      "action": "enter phone number"
    },
    {
      "type": "input",
      "style": "standard",
      "action": "enter promo code",
      "label": "Have a promo code?"
    },
    {
      "label": "APPLY",
      "action": "apply promo code",
      "style": "secondary",
      "type": "button"
    }
  ],
  "components": [
    "breadcrumb navigation",
    "ticket summary card",
    "buyer information form",
    "payment methods tabs",
    "credit card form",
    "order summary card",
    "promo code input",
    "footer"
  ],
  "screenshotUrl": "https://firebasestorage.googleapis.com/v0/b/mindmap-prd-tool.firebasestorage.app/o/project-uploads%2FNCwF9JxkW9U5C4X6KhBvrUO3zl13%2F1773692936890-224765.png?alt=media&token=cf3a0e1c-b174-4595-9117-f00e599033c0",
  "visualDescription": "The checkout page is designed with a modern and minimalistic style, featuring a two-column layout. The header includes a breadcrumb navigation for easy backtracking to the event page. The left column contains a ticket summary card with a vivid event image, event name in bold italic, and pricing details. Below, the buyer information form has three input fields for full name, email address, and phone number, each with rounded corners and subtle shadows. The payment methods section offers three tabs: Credit/Debit Card, Bank Transfer, and Mobile Money, with the first tab highlighted. The credit card form includes fields for card number, expiry date, and CVV, with a lock icon for security emphasis. The right column features an order summary card with a breakdown of costs and a prominent 'PAY NOW' button in the primary brand color. Below, a promo code input field with an 'APPLY' button is provided. The footer is simple, with links to privacy policy, terms of service, and support. The color scheme is dominated by a soft white background, accented with the brand's primary orange and secondary red, creating a warm and inviting interface. Typography is clean, with bold headings and standard body text. Spacing is generous, ensuring a clutter-free experience. The use of shadows is minimal, providing just enough depth to distinguish elements without overwhelming the design.",
  "asciiDiagram": "+------------------------------------------+\n|  [=] EventFlow          Back to Event    |\n+------------------------------------------+\n| Ticket Summary                           |\n| +--------------------------------------+ |\n| |  [Image]  Neon Dreams Music Festival  | |\n| |          VIP All-Access Pass         | |\n| |  PRICE $249.00                       | |\n| |  Quantity: 1                         | |\n| |  May 12-14, 2024 - Miami, FL         | |\n| +--------------------------------------+ |\n| Buyer Information                       |\n| +--------------------------------------+ |\n| | Full Name: [______________________]  | |\n| | Email Address: [__________________]  | |\n| | Phone Number: [+1 (___) ___-_____]  | |\n| +--------------------------------------+ |\n| Payment Methods                         |\n| +----------------+---------------------+ |\n| | Credit/Debit   | Bank Transfer       | |\n| | Card           |                     | |\n| | [Card Details] | [Bank Details]      | |\n| +----------------+---------------------+ |\n+------------------------------------------+\n| Order Summary                            |\n| +--------------------------------------+ |\n| | Subtotal: $249.00                    | |\n| | Service Fee: $12.45                  | |\n| | VAT (5%): $13.07                     | |\n| | Total Amount: $274.52                | |\n| | [PAY NOW]                            | |\n| +--------------------------------------+ |\n| Have a promo code? [________] [APPLY]  | |\n+------------------------------------------+\n| Footer: Privacy Policy | Terms | Support |\n+------------------------------------------+",
  "layout": "two-column",
  "borderRadius": {
    "badges": "12px",
    "buttons": "8px",
    "cards": "12px",
    "images": "8px",
    "inputs": "8px"
  },
  "shadows": {
    "cards": "0 1px 3px rgba(0,0,0,0.1)",
    "buttons": "none",
    "intensity": "minimal",
    "dropdowns": "none"
  },
  "matchConfidence": 0.9
}
Visual Description: The checkout page is designed with a modern and minimalistic style, featuring a two-column layout. The header includes a breadcrumb navigation for easy backtracking to the event page. The left column contains a ticket summary card with a vivid event image, event name in bold italic, and pricing details. Below, the buyer information form has three input fields for full name, email address, and phone number, each with rounded corners and subtle shadows. The payment methods section offers three tabs: Credit/Debit Card, Bank Transfer, and Mobile Money, with the first tab highlighted. The credit card form includes fields for card number, expiry date, and CVV, with a lock icon for security emphasis. The right column features an order summary card with a breakdown of costs and a prominent 'PAY NOW' button in the primary brand color. Below, a promo code input field with an 'APPLY' button is provided. The footer is simple, with links to privacy policy, terms of service, and support. The color scheme is dominated by a soft white background, accented with the brand's primary orange and secondary red, creating a warm and inviting interface. Typography is clean, with bold headings and standard body text. Spacing is generous, ensuring a clutter-free experience. The use of shadows is minimal, providing just enough depth to distinguish elements without overwhelming the design.
Flow Name: Secure Checkout & Payment Processing
Tech Stack Used:
  1. @stripe/react-stripe-js
  2. @paypal/paypal-js
  3. react-router-dom
Referenced Schemas:
  1. orders
  2. order_items
  3. users

---
Generated by VisualPRD