<?php

namespace App\Features\admin\Controllers;

use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = now();

        $metrics = [
            'total_revenue' => (float) Payment::where('status', 'success')->whereNotNull('paid_at')->sum('amount'),
            'total_events' => Event::count(),
            'total_users' => User::count(),
            'total_tickets_sold' => Ticket::count(),
            'payments_today' => (int) Payment::whereDate('created_at', $now->toDateString())->count(),
            'events_today' => (int) Event::whereDate('created_at', $now->toDateString())->count(),
            'users_today' => (int) User::whereDate('created_at', $now->toDateString())->count(),
        ];

        $quickStats = [
            'pending_payouts' => 0.0,
            'open_disputes' => 0,
            'active_events' => (int) Event::where('status', 'published')->where('start_datetime', '>=', $now)->count(),
            'completed_events' => (int) Event::where('status', 'published')->where('end_datetime', '<', $now)->count(),
        ];

        $activity = Payment::whereDate('created_at', '>=', $now->subDays(7)->toDateString())
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'type' => 'payment',
                'status' => $p->status->value ?? $p->status,
                'amount' => (float) $p->amount,
                'currency' => $p->currency ?? 'usd',
                'user_id' => $p->user_id,
                'created_at' => $p->created_at->toDateTimeString(),
            ]);

        $alerts = [];
        $recentFailed = Payment::where('status', 'failed')->whereDate('created_at', '>=', $now->subDays(1)->toDateString())->count();
        if ($recentFailed > 0) {
            $alerts[] = ['type' => 'payment_failures', 'message' => "{$recentFailed} failed payments in the last 24 hours", 'severity' => 'warning'];
        }

        return response()->json([
            'metrics' => $metrics,
            'quickStats' => $quickStats,
            'activity' => $activity,
            'alerts' => $alerts,
        ]);
    }
}


