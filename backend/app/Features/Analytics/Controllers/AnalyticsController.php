<?php

namespace App\Features\Analytics\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Analytics\Models\SalesTimeline;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get pre-aggregated sales velocity data (daily or hourly).
     */
    public function getSalesVelocity(Request $request, $eventId)
    {
        $interval = $request->query('interval', 'daily'); // 'daily' or 'hourly'
        
        // Check if there is actual database data
        $hasData = SalesTimeline::where('event_id', $eventId)->exists();
        
        if (!$hasData) {
            // Fallback: Generate clean, pre-aggregated mock sales velocity data
            return response()->json([
                'success' => true,
                'interval' => $interval,
                'data' => $this->generateMockSalesVelocity($eventId, $interval),
                'aggregated_on_server' => true,
            ]);
        }
        
        // Database level pre-aggregation
        $driver = DB::connection()->getDriverName();
        $query = SalesTimeline::where('event_id', $eventId);
        
        if ($interval === 'hourly') {
            if ($driver === 'sqlite') {
                $query->select(
                    DB::raw("strftime('%Y-%m-%d %H:00:00', timestamp) as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            } elseif ($driver === 'mysql') {
                $query->select(
                    DB::raw("DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            } else { // postgresql
                $query->select(
                    DB::raw("date_trunc('hour', timestamp) as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            }
        } else { // default to 'daily'
            if ($driver === 'sqlite') {
                $query->select(
                    DB::raw("strftime('%Y-%m-%d', timestamp) as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            } elseif ($driver === 'mysql') {
                $query->select(
                    DB::raw("DATE_FORMAT(timestamp, '%Y-%m-%d') as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            } else { // postgresql
                $query->select(
                    DB::raw("date_trunc('day', timestamp) as time_bucket"),
                    DB::raw("SUM(sales_count) as ticketsSold"),
                    DB::raw("SUM(revenue) as revenue")
                );
            }
        }
        
        $aggregatedData = $query->groupBy('time_bucket')
            ->orderBy('time_bucket', 'asc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->time_bucket,
                    'ticketsSold' => (int) $row->ticketsSold,
                    'revenue' => (float) $row->revenue,
                ];
            });
            
        return response()->json([
            'success' => true,
            'interval' => $interval,
            'data' => $aggregatedData,
            'aggregated_on_server' => true,
        ]);
    }

    /**
     * Get event summary metrics.
     */
    public function getSummary(Request $request, $eventId)
    {
        return response()->json([
            'success' => true,
            'eventId' => $eventId,
            'metrics' => [
                'totalRevenue' => 14520.00,
                'ticketsSold' => 324,
                'ticketCapacity' => 500,
                'conversionRate' => 18.4, // percentage
                'pageViews' => 1760,
                'refundedTickets' => 4,
            ]
        ]);
    }

    /**
     * Get detailed breakdown of analytics.
     */
    public function getDetailed(Request $request, $eventId)
    {
        return response()->json([
            'success' => true,
            'eventId' => $eventId,
            'funnel' => [
                ['stage' => 'Page Views', 'count' => 1760],
                ['stage' => 'Ticket Selection', 'count' => 840],
                ['stage' => 'Checkout Form', 'count' => 420],
                ['stage' => 'Completed Orders', 'count' => 324],
            ],
            'channels' => [
                ['name' => 'Direct', 'value' => 45],
                ['name' => 'Social Media', 'value' => 30],
                ['name' => 'Email Marketing', 'value' => 15],
                ['name' => 'Referrals', 'value' => 10],
            ]
        ]);
    }

    /**
     * Get comparison between multiple events.
     */
    public function getComparison(Request $request)
    {
        return response()->json([
            'success' => true,
            'comparison' => [
                ['name' => 'Summer Festival', 'ticketsSold' => 324, 'revenue' => 14520.00],
                ['name' => 'Winter Gala', 'ticketsSold' => 150, 'revenue' => 7500.00],
                ['name' => 'Spring Concert', 'ticketsSold' => 410, 'revenue' => 16400.00],
            ]
        ]);
    }

    /**
     * Generates a clean, pre-aggregated realistic time series for demonstration.
     */
    private function generateMockSalesVelocity($eventId, $interval)
    {
        $data = [];
        $now = Carbon::now();

        if ($interval === 'hourly') {
            // Last 24 hours
            for ($i = 23; $i >= 0; $i--) {
                $time = (clone $now)->subHours($i);
                // Ensure a nice trend: higher sales count in evening hours
                $hour = (int)$time->format('H');
                $base = 2;
                if ($hour >= 17 && $hour <= 22) {
                    $base = 15;
                } elseif ($hour >= 8 && $hour <= 16) {
                    $base = 7;
                }
                $ticketsSold = rand($base - 2 >= 0 ? $base - 2 : 0, $base + 3);
                $data[] = [
                    'date' => $time->format('Y-m-d H:00:00'),
                    'ticketsSold' => $ticketsSold,
                    'revenue' => $ticketsSold * 45.0,
                ];
            }
        } else {
            // Last 14 days
            for ($i = 13; $i >= 0; $i--) {
                $time = (clone $now)->subDays($i);
                // Creating an upward curve trend
                $multiplier = (14 - $i) * 1.5;
                $ticketsSold = rand(5, 12) + (int)round($multiplier);
                $data[] = [
                    'date' => $time->format('Y-m-d'),
                    'ticketsSold' => $ticketsSold,
                    'revenue' => $ticketsSold * 45.00,
                ];
            }
        }

        return $data;
    }
}

