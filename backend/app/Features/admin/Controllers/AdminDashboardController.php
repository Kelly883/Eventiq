<?php

namespace App\Features\admin\Controllers;

use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Features\Compliance\Services\AuditLogService;
use App\Features\Fraud\Models\FraudEvent;
use App\Features\Payouts\Models\Payout;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private AuditLogService $auditLogService)
    {
    }

    /**
     * GET /api/admin/dashboard
     * Legacy aggregate dashboard — kept for backward compatibility.
     */
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

        $this->auditLogService->log('admin.dashboard.viewed', 'dashboard', 'overview', [], $request->user()?->id);

        return response()->json([
            'metrics' => $metrics,
            'quickStats' => $quickStats,
            'activity' => $activity,
            'alerts' => $alerts,
        ]);
    }

    /**
     * GET /api/admin/dashboard/overview
     */
    public function overview(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:24h,7d,30d,all_time'],
        ]);

        $period = $validated['period'] ?? '30d';
        [$startDate, $endDate] = $this->resolvePeriod($period);

        $metrics = $this->computeMetrics($startDate, $endDate);
        $previousMetrics = $this->computeMetrics(
            $startDate->clone()->subSeconds(abs($endDate->diffInSeconds($startDate))),
            $startDate->clone()->subSecond()
        );
        $trends = $this->buildTrends($metrics, $previousMetrics);

        $this->auditLogService->log('admin.dashboard.overview', 'dashboard', 'overview', ['period' => $period], $request->user()?->id);

        return response()->json([
            'success' => true,
            'period' => $period,
            'startDate' => $startDate->toIso8601String(),
            'endDate' => $endDate->toIso8601String(),
            'metrics' => $metrics,
            'trends' => $trends,
        ]);
    }

    /**
     * GET /api/admin/dashboard/activity-feed
     */
    public function activityFeed(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'action' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:success,failure,warning'],
        ]);

        $limit = $validated['limit'] ?? 20;
        $offset = $validated['offset'] ?? 0;

        $query = AuditLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit);

        if (!empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $total = (clone $query)->count();
        $activities = $query->get()->map(fn (AuditLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->getActionLabel(),
            'status' => $log->status,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ] : null,
            'targetType' => $log->target_type,
            'targetId' => $log->target_id,
            'ipAddress' => $log->ip_address,
            'createdAt' => $log->created_at?->toIso8601String(),
        ]);

        $this->auditLogService->log('admin.dashboard.activity_feed', 'dashboard', 'activity_feed', $validated, $request->user()?->id);

        return response()->json([
            'success' => true,
            'data' => $activities,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * GET /api/admin/dashboard/alerts
     */
    public function alerts(Request $request)
    {
        $validated = $request->validate([
            'severity' => ['nullable', 'in:critical,warning,info'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;
        $severityFilter = $validated['severity'] ?? null;

        $alerts = $this->buildAlerts($severityFilter, $limit, $offset);

        $this->auditLogService->log('admin.dashboard.alerts', 'dashboard', 'alerts', ['severity' => $severityFilter], $request->user()?->id);

        return response()->json([
            'success' => true,
            'data' => $alerts['items'],
            'pagination' => [
                'total' => $alerts['total'],
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    private function resolvePeriod(string $period): array
    {
        $end = Carbon::now();

        return match ($period) {
            '24h' => [(clone $end)->subDay(), $end],
            '7d' => [(clone $end)->subDays(7), $end],
            '30d' => [(clone $end)->subDays(30), $end],
            'all_time' => [Carbon::createFromTimestamp(0), $end],
            default => [(clone $end)->subDays(30), $end],
        };
    }

    private function computeMetrics(Carbon $start, Carbon $end): array
    {
        $revenue = (float) Payment::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $eventsQuery = Event::whereBetween('created_at', [$start, $end]);
        $eventsCreated = $eventsQuery->count();

        $usersRegistered = User::whereBetween('created_at', [$start, $end])->count();
        $ticketsSold = Ticket::whereBetween('created_at', [$start, $end])->count();

        $completedPayouts = Payout::where('status', Payout::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->sum('payout_amount');

        $failedPayouts = Payout::where('status', Payout::STATUS_FAILED)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $failedPayments = Payment::where('status', 'failed')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'revenue' => $revenue,
            'eventsCreated' => (int) $eventsCreated,
            'usersRegistered' => (int) $usersRegistered,
            'ticketsSold' => (int) $ticketsSold,
            'completedPayouts' => (float) $completedPayouts,
            'failedPayouts' => (int) $failedPayouts,
            'failedPayments' => (int) $failedPayments,
        ];
    }

    private function buildTrends(array $current, array $previous): array
    {
        return [
            'revenue' => $this->trend($current['revenue'], $previous['revenue']),
            'ticketsSold' => $this->trend($current['ticketsSold'], $previous['ticketsSold']),
            'eventsCreated' => $this->trend($current['eventsCreated'], $previous['eventsCreated']),
            'usersRegistered' => $this->trend($current['usersRegistered'], $previous['usersRegistered']),
            'completedPayouts' => $this->trend($current['completedPayouts'], $previous['completedPayouts']),
        ];
    }

    private function trend(float|int $current, float|int $previous): array
    {
        $current = (float) $current;
        $previous = (float) $previous;
        $delta = $current - $previous;
        $pct = $previous > 0 ? round(($delta / $previous) * 100, 2) : ($current > 0 ? 100.0 : 0.0);

        return [
            'direction' => $pct > 0.01 ? 'up' : ($pct < -0.01 ? 'down' : 'flat'),
            'percentageChange' => $pct,
            'delta' => round($delta, 2),
        ];
    }

    private function buildAlerts(?string $severityFilter, int $limit, int $offset): array
    {
        $alerts = [];

        $fraudQuery = FraudEvent::query()
            ->whereIn('status', ['flagged', 'auto_blocked'])
            ->orderByDesc('created_at');

        if ($severityFilter === 'critical') {
            $fraudQuery->whereIn('risk_level', ['high']);
        } elseif ($severityFilter === 'warning') {
            $fraudQuery->whereIn('risk_level', ['medium']);
        } elseif ($severityFilter === 'info') {
            $fraudQuery->whereIn('risk_level', ['low']);
        }

        foreach ($fraudQuery->get() as $fraud) {
            $severity = match ($fraud->risk_level) {
                'high' => 'critical',
                'medium' => 'warning',
                default => 'info',
            };

            if ($severityFilter && $severityFilter !== $severity) {
                continue;
            }

            $alerts[] = [
                'id' => $fraud->id,
                'type' => 'fraud',
                'severity' => $severity,
                'message' => $fraud->getReadableEventType() . ' — Risk score: ' . $fraud->getFormattedRiskScore(),
                'source' => 'fraud_events',
                'createdAt' => $fraud->created_at?->toIso8601String(),
                'metadata' => [
                    'riskLevel' => $fraud->risk_level,
                    'riskScore' => (float) $fraud->risk_score,
                    'fraudType' => $fraud->fraud_type,
                    'status' => $fraud->status,
                ],
            ];
        }

        $failedPayouts = Payout::where('status', Payout::STATUS_FAILED)
            ->orderByDesc('created_at')
            ->get();

        foreach ($failedPayouts as $payout) {
            if ($severityFilter && $severityFilter !== 'critical') {
                continue;
            }

            $alerts[] = [
                'id' => $payout->id,
                'type' => 'payout_failure',
                'severity' => 'critical',
                'message' => 'Payout ' . $payout->id . ' failed for organizer ' . $payout->organizer_id,
                'source' => 'payouts',
                'createdAt' => $payout->created_at?->toIso8601String(),
                'metadata' => [
                    'payoutAmount' => (float) $payout->payout_amount,
                    'currency' => $payout->currency,
                    'failureReason' => $payout->failure_reason,
                    'retryCount' => (int) $payout->retry_count,
                ],
            ];
        }

        $auditFailures = AuditLog::whereIn('status', ['failure', 'warning'])
            ->orderByDesc('created_at')
            ->get();

        foreach ($auditFailures as $log) {
            $severity = $log->status === 'failure' ? 'critical' : 'warning';

            if ($severityFilter && $severityFilter !== $severity) {
                continue;
            }

            $alerts[] = [
                'id' => $log->id,
                'type' => 'audit_failure',
                'severity' => $severity,
                'message' => ($log->description ?: $log->getActionLabel()) . ($log->error_message ? ': ' . $log->error_message : ''),
                'source' => 'audit_logs',
                'createdAt' => $log->created_at?->toIso8601String(),
                'metadata' => [
                    'action' => $log->action,
                    'targetType' => $log->target_type,
                    'targetId' => $log->target_id,
                    'errorCode' => $log->error_code,
                    'userId' => $log->user_id,
                ],
            ];
        }

        usort($alerts, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
            $aSeverity = $severityOrder[$a['severity']] ?? 9;
            $bSeverity = $severityOrder[$b['severity']] ?? 9;

            if ($aSeverity !== $bSeverity) {
                return $aSeverity <=> $bSeverity;
            }

            return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? '');
        });

        $total = count($alerts);
        $paged = array_slice($alerts, $offset, $limit);

        return [
            'total' => $total,
            'items' => $paged,
        ];
    }
}
