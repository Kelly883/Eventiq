declare module 'react-big-calendar' {
  import * as React from 'react';

  export interface CalendarProps {
    localizer: any;
    events: any[];
    startAccessor: string;
    endAccessor: string;
    view: string;
    views?: string[];
    onView?: (view: string) => void;
    onSelectEvent?: (event: any) => void;
    onSelectSlot?: (slotInfo: { start: Date; end: Date; action: string }) => void;
    dayProp?: (date: Date, view: string) => any;
    eventProp?: (event: any, start: Date, end: Date) => any;
    style?: React.CSSProperties;
    [key: string]: any;
  }

  export class Calendar extends React.Component<CalendarProps> {}
  export function dateFnsLocalizer(opts: {
    format: any;
    parse: any;
    startOfWeek: any;
    getDay: any;
    locales: Record<string, any>;
  }): any;
}
