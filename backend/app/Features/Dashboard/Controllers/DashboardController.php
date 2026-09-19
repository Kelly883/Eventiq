<?php

namespace App\Features\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Dashboard\Models\OrganizerDashboardPreferences;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    private function getOrganizerForUser($user)
    {
        return Organizer::where('user_id', $user->id)->first();
    }

    private function authorizeDashboardAccess(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->hasRole('admin')) {
            return; // Admin has access
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'You do not have access to this dashboard.');
        }
    }

    /**
     * GET /api/organizer/dashboard/metrics
     * High-level metrics for the organizer dashboard.
     * Results cached for 3 minutes since organizers don't need real-time data.
     */
    public function getMetrics(Request $request)
    {
        $this->authorizeDashboardAccess($request);

        $key = 'dashboard.metrics.' . $request->user()->id . '.' . $request->query('eventId', 'null');

        return Cache::remember($key, minutes: 3, function () use ($request) {
            $user = $request->user();
            $eventId = $request->query('eventId');

            $query = Event::query();

            if (!$user->hasRole('admin')) {
                $organizer = $this->getOrganizerForUser($user);
                if ($organizer) {
                    $query->where('organizer_id', $organizer->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($eventId) {
                $query->where('id', $eventId);
            }

            $totalEvents = $query->count();
            $eventsPublished = (clone $query)->where('status', 'published')->count();

            // Get tickets sold and revenue from analytics metrics
            $eventIds = $query->pluck('id')->toArray();

            $ticketsSold = \App\Models\AnalyticsEventsMetric::whereIn('event_id', $eventIds)
                ->sum('total_tickets_sold');

            $revenue = \App\Models\AnalyticsEventsMetric::whereIn('event_id', $eventIds)
                ->sum('total_revenue');

            return response()->json([
                'success' => true,
                'metrics' => [
                    'totalEvents' => $totalEvents,
                    'totalTicketsSold' => (int) $ticketsSold,
                    'totalRevenue' => (float) $revenue,
                    'eventsPublished' => $eventsPublished,
                ],
            ]);
        });
    }

    /**
     * GET /api/organizer/dashboard/preferences
     * Returns dashboard preferences for the current user.
     */
    public function getPreferences(Request $request)
    {
        $this->authorizeDashboardAccess($request);

        $user = $request->user();

        if ($user->hasRole('admin')) {
            // Admin sees default preferences (no organizer record)
            return response()->json([
                'success' => true,
                'preferences' => $this->getDefaultPreferences(),
            ]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::where('organizer_id', $organizer->id)->first();

        if (!$prefs) {
            return response()->json([
                'success' => true,
                'preferences' => $this->getDefaultPreferences(),
            ]);
        }

        return response()->json([
            'success' => true,
            'preferences' => $this->formatPreferences($prefs),
        ]);
    }

    /**
     * PUT /api/organizer/dashboard/preferences
     * Update dashboard preferences.
     */
    public function updatePreferences(Request $request)
    {
        $this->authorizeDashboardAccess($request);

        $user = $request->user();

        // Validate input
        $validated = $request->validate([
            'default_event_filter' => 'nullable|in:all,upcoming,past,published,draft',
            'default_date_range' => 'nullable|in:last_7_days,last_30_days,last_90_days,all_time',
            'show_activity_feed' => 'nullable|boolean',
            'auto_refresh_enabled' => 'nullable|boolean',
        ]);

        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => true,
                'preferences' => $this->getDefaultPreferences(),
            ]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::updateOrCreate(
            ['organizer_id' => $organizer->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'preferences' => $this->formatPreferences($prefs),
        ]);
    }

    /**
     * GET /api/organizer/dashboard/activity-feed
     * Returns recent activity for the dashboard.
     */
    public function getActivityFeed(Request $request)
    {
        $this->authorizeDashboardAccess($request);

        $user = $request->user();

        if ($user->hasRole('admin')) {
            return response()->json([
                'success' => true,
                'enabled' => true,
                'activities' => [],
            ]);
        }

        $organizer = $this->getOrganizerForUser($user);
        if (!$organizer) {
            abort(403, 'No organizer profile found.');
        }

        $prefs = OrganizerDashboardPreferences::where('organizer_id', $organizer->id)->first();

        if (!$prefs || !$prefs->show_activity_feed) {
            return response()->json([
                'success' => true,
                'enabled' => false,
                'activities' => [],
            ]);
        }

        // Get recent sales timeline entries for activity feed
        $recentSales = \App\Models\AnalyticsSalesTimeline::whereIn('event_id', function ($query) use ($organizer) {
            $query->select('id')->from('events')->where('organizer_id', $organizer->id);
        })
            ->orderBy('sale_timestamp', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'type' => 'ticket_sale',
                    'message' => "Ticket sale: {$sale->quantity} ticket(s) for \${$sale->total_amount}",
                    'timestamp' => $sale->sale_timestamp?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'enabled' => true,
            'activities' => $recentSales,
        ]);
    }

    private function getDefaultPreferences(): array
    {
        return [
            'defaultEventFilter' => 'all',
            'defaultDateRange' => 'last_30_days',
            'showActivityFeed' => true,
            'autoRefreshEnabled' => false,
        ];
    }

    private function formatPreferences(OrganizerDashboardPreferences $prefs): array
    {
        return [
            'defaultEventFilter' => $prefs->default_event_filter,
            'defaultDateRange' => $prefs->default_date_range,
            'showActivityFeed' => (bool) $prefs->show_activity_feed,
            'autoRefreshEnabled' => (bool) $prefs->auto_refresh_enabled,
        ];
    }
}
