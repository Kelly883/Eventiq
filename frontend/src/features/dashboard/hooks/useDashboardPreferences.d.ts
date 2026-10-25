export interface DashboardFilters {
  dateRange: string;
  status: string;
  category: string;
  search: string;
}

export interface DashboardPreferencesState {
  expandedEventId: string | number | null;
  events: unknown[];
  filters: DashboardFilters;
  isActivityFeedVisible: boolean;
  setExpandedEventId: (eventId: string | number | null) => void;
  setEvents: (events: unknown[]) => void;
  setFilters: (filters: Partial<DashboardFilters>) => void;
  toggleActivityFeed: () => void;
  setActivityFeedVisible: (visible: boolean) => void;
  fetchEvents: () => Promise<void>;
}

export declare const useDashboardPreferences: {
  (): DashboardPreferencesState;
  getState: () => DashboardPreferencesState;
  setState: (partial: Partial<DashboardPreferencesState>) => void;
};
