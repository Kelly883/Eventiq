import React from 'react';
import { AttendeeBottomNav } from './AttendeeBottomNav';

/**
 * Shared layout for every attendee dashboard page.
 *
 * - Renders the page content ({children}).
 * - Renders the reusable bottom navigation.
 * - Does NOT render a sidebar.
 * - Adds bottom padding so content is not hidden behind the fixed navigation.
 */
const AttendeeLayout = ({ children }) => (
  <div className="attendee-layout min-h-screen flex flex-col bg-[#F7F8FA]">
    <main className="flex-1 pb-24 overflow-y-auto">{children}</main>
    <AttendeeBottomNav />
  </div>
);

export default AttendeeLayout;
