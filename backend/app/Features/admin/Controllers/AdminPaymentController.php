<?php

namespace App\Features\admin\Controllers;

use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::query()
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status');
                $q->where(function ($sub) use ($status) {
                    $sub->where('status', $status)
                        ->orWhere('status', PaymentStatus::tryFrom($status)?->value ?? $status);
                });
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        $report = Payment::query()
            ->selectRaw('status, count(*) as count, sum(amount) as gross, sum(net_amount) as net, sum(fees) as fees')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status => [
                'count' => (int) $row->count,
                'gross' => (float) $row->gross,
                'net' => (float) $row->net,
                'fees' => (float) $row->fees,
            ]]);

        $chart = Payment::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->selectRaw("DATE(created_at) as day, status, count(*) as count")
            ->groupBy('day', 'status')
            ->orderBy('day')
            ->get()
            ->groupBy('day');

        return response()->json([
            'payments' => $payments,
            'report' => $report,
            'chart' => $chart,
        ]);
    }
}


