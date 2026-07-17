<?php

namespace App\Features\Refunds\Controllers;

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Requests\UpdateRefundRequest;
use App\Features\Refunds\Resources\RefundRequestResource;
use App\Features\Refunds\Services\RefundService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminRefundController extends Controller
{
    public function __construct(private RefundService $refundService)
    {
    }

    /**
     * GET /api/admin/refunds
     */
    public function index(Request $request)
    {
        $query = RefundRequest::with(['ticket.event', 'user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return RefundRequestResource::collection($query->latest()->paginate(20));
    }

    /**
     * PUT /api/admin/refunds/{id}/approve
     */
    public function approve(UpdateRefundRequest $request, int $id)
    {
        try {
            $refundRequest = $this->refundService->approve(
                $id,
                $request->user()->id,
                $request->validated('approved_amount'),
                $request->validated('admin_notes')
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Refund approved but gateway processing failed: ' . $e->getMessage()], 502);
        }

        return new RefundRequestResource($refundRequest);
    }

    /**
     * PUT /api/admin/refunds/{id}/reject
     */
    public function reject(UpdateRefundRequest $request, int $id)
    {
        $refundRequest = $this->refundService->reject(
            $id,
            $request->user()->id,
            $request->validated('admin_notes') ?? ''
        );

        return new RefundRequestResource($refundRequest);
    }
}
