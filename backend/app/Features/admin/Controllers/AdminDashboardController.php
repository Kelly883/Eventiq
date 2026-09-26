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
use Illuminate\Support\Facades\DB;

trait AlertHelpers
{
    private function getActionLabel(string $action): string
    {
        return match ($action) {
            'user_login' => 'User Login',
            'user_logout' => 'User Logout',
            'user_suspended' => 'User Suspended',
            'event_created' => 'Event Created',
            'event_approved' => 'Event Approved',
            'event_flagged' => 'Event Flagged',
            'event_cancelled' => 'Event Cancelled',
            'payment_processed' => 'Payment Processed',
            'payment_refunded' => 'Payment Refunded',
            'refund.requested' => 'Refund Requested',
            'refund_approved' => 'Refund Approved',
            'refund_rejected' => 'Refund Rejected',
            'payout_approved' => 'Payout Approved',
            'payout_rejected' => 'Payout Rejected',
            'ticket_checked_in' => 'Ticket Checked In',
            'ticket_voided' => 'Ticket Voided',
            'ticket.purged' => 'Ticket Purged',
            'fraud_flagged' => 'Fraud Flagged',
            'fraud_approved' => 'Fraud Approved',
            'admin_setting_changed' => 'Admin Setting Changed',
            'user_permission_changed' => 'User Permission Changed',
            'data_export_requested' => 'Data Export Requested',
            default => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    private function getReadableEventType(string $fraudType): string
    {
        return match ($fraudType) {
            'duplicate_ticket_attempt' => 'Duplicate Ticket Attempt',
            'velocity_check_failed' => 'Velocity Check Failed',
            'payment_pattern_suspicious' => 'Suspicious Payment Pattern',
            'device_fingerprint_mismatch' => 'Device Fingerprint Mismatch',
            'geolocation_anomaly' => 'Geolocation Anomaly',
            'card_testing' => 'Card Testing',
            'high_risk_payment_method' => 'High Risk Payment Method',
            'duplicate_checkin' => 'Duplicate Check-in',
            'invalid_qr' => 'Invalid QR Code',
            'manual_override' => 'Manual Override',
            default => ucfirst(str_replace('_', ' ', $fraudType)),
        };
    }
}

class AdminDashboardController extends Controller
{
    use AlertHelpers;

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
        $isNewMetric = $previous == 0 && $current > 0;

        if ($previous > 0) {
            $pct = round(($delta / $previous) * 100, 2);
        } elseif ($current > 0) {
            $pct = 100.0;
        } else {
            $pct = 0.0;
        }

        return [
            'direction' => $isNewMetric ? 'up' : ($pct > 0.01 ? 'up' : ($pct < -0.01 ? 'down' : 'flat')),
            'percentageChange' => $pct,
            'delta' => round($delta, 2),
            'newMetric' => $isNewMetric,
        ];
    }

    private function buildAlerts(?string $severityFilter, int $limit, int $offset): array
    {
        $sql = "
            SELECT 
                id,
                type,
                severity,
                created_at,
                fraud_type,
                risk_level,
                risk_score,
                fraud_status,
                payout_amount,
                currency,
                failure_reason,
                retry_count,
                organizer_id,
                action,
                target_type,
                target_id,
                error_code,
                user_id,
                description,
                error_message
            FROM (
                SELECT 
                    id,
                    'fraud' as type,
                    CASE risk_level WHEN 'high' THEN 'critical' WHEN 'medium' THEN 'warning' ELSE 'info' END as severity,
                    created_at,
                    fraud_type,
                    risk_level,
                    risk_score,
                    status as fraud_status,
                    NULL as payout_amount,
                    NULL as currency,
                    NULL as failure_reason,
                    NULL as retry_count,
                    NULL as organizer_id,
                    NULL as action,
                    NULL as target_type,
                    NULL as target_id,
                    NULL as error_code,
                    NULL as user_id,
                    NULL as description,
                    NULL as error_message
                FROM fraud_events
                WHERE status IN ('flagged', 'auto_blocked')
                
                UNION ALL
                
                SELECT 
                    id,
                    'payout_failure' as type,
                    'critical' as severity,
                    created_at,
                    NULL as fraud_type,
                    NULL as risk_level,
                    NULL as risk_score,
                    NULL as fraud_status,
                    payout_amount,
                    currency,
                    failure_reason,
                    retry_count,
                    organizer_id,
                    NULL as action,
                    NULL as target_type,
                    NULL as target_id,
                    NULL as error_code,
                    NULL as user_id,
                    NULL as description,
                    NULL as error_message
                FROM payouts
                WHERE status = 'failed'
                
                UNION ALL
                
                SELECT 
                    id,
                    'audit_failure' as type,
                    CASE status WHEN 'failure' THEN 'critical' WHEN 'warning' THEN 'warning' ELSE 'info' END as severity,
                    created_at,
                    NULL as fraud_type,
                    NULL as risk_level,
                    NULL as risk_score,
                    NULL as fraud_status,
                    NULL as payout_amount,
                    NULL as currency,
                    NULL as failure_reason,
                    NULL as retry_count,
                    NULL as organizer_id,
                    action,
                    target_type,
                    target_id,
                    error_code,
                    user_id,
                    description,
                    error_message
                FROM audit_logs
                WHERE status IN ('failure', 'warning')
            ) as alerts
        ";

        $bindings = [];

        if ($severityFilter) {
            $sql .= " WHERE severity = ?";
            $bindings[] = $severityFilter;
        }

        $sql .= " ORDER BY 
            CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END,
            created_at DESC
            LIMIT ? OFFSET ?";

        $bindings[] = $limit;
        $bindings[] = $offset;

        $alerts = collect(DB::select($sql, $bindings))->map(function ($alert) {
            switch ($alert->type) {
                case 'fraud':
                    return [
                        'id' => $alert->id,
                        'type' => 'fraud',
                        'severity' => $alert->severity,
                        'message' => $this->getReadableEventType($alert->fraud_type) . ' — Risk score: ' . number_format((float) $alert->risk_score, 2),
                        'source' => 'fraud_events',
                        'createdAt' => $alert->created_at ? \Carbon\Carbon::parse($alert->created_at)->toIso8601String() : null,
                        'metadata' => [
                            'riskLevel' => $alert->risk_level,
                            'riskScore' => (float) $alert->risk_score,
                            'fraudType' => $alert->fraud_type,
                            'status' => $alert->fraud_status,
                        ],
                    ];
                case 'payout_failure':
                    return [
                        'id' => $alert->id,
                        'type' => 'payout_failure',
                        'severity' => 'critical',
                        'message' => 'Payout ' . $alert->id . ' failed for organizer ' . $alert->organizer_id,
                        'source' => 'payouts',
                        'createdAt' => $alert->created_at ? \Carbon\Carbon::parse($alert->created_at)->toIso8601String() : null,
                        'metadata' => [
                            'payoutAmount' => (float) $alert->payout_amount,
                            'currency' => $alert->currency,
                            'failureReason' => $alert->failure_reason,
                            'retryCount' => (int) $alert->retry_count,
                        ],
                    ];
                case 'audit_failure':
                    return [
                        'id' => $alert->id,
                        'type' => 'audit_failure',
                        'severity' => $alert->severity,
                        'message' => ($alert->description ?: $this->getActionLabel($alert->action)) . ($alert->error_message ? ': ' . $alert->error_message : ''),
                        'source' => 'audit_logs',
                        'createdAt' => $alert->created_at ? \Carbon\Carbon::parse($alert->created_at)->toIso8601String() : null,
                        'metadata' => [
                            'action' => $alert->action,
                            'targetType' => $alert->target_type,
                            'targetId' => $alert->target_id,
                            'errorCode' => $alert->error_code,
                            'userId' => $alert->user_id,
                        ],
                    ];
            }
        });

        $fraudCount = FraudEvent::whereIn('status', ['flagged', 'auto_blocked'])
            ->when($severityFilter === 'critical', fn($q) => $q->where('risk_level', 'high'))
            ->when($severityFilter === 'warning', fn($q) => $q->where('risk_level', 'medium'))
            ->when($severityFilter === 'info', fn($q) => $q->where('risk_level', 'low'))
            ->count();

        $payoutCount = match ($severityFilter) {
            'critical' => Payout::where('status', Payout::STATUS_FAILED)->count(),
            default => 0,
        };

        $auditCount = AuditLog::whereIn('status', ['failure', 'warning'])
            ->when($severityFilter === 'critical', fn($q) => $q->where('status', 'failure'))
            ->when($severityFilter === 'warning', fn($q) => $q->where('status', 'warning'))
            ->when($severityFilter === 'info', fn($q) => $q->where('status', 'info'))
            ->count();

        return [
            'total' => $fraudCount + $payoutCount + $auditCount,
            'items' => $alerts,
        ];
    }
}
