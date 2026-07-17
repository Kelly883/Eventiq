<?php

namespace App\Features\Refunds\Controllers;

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Requests\StoreRefundRequest;
use App\Features\Refunds\Resources\RefundRequestResource;
use App\Features\Refunds\Services\RefundService;
use App\Http\Controllers\Controller;

class RefundController extends Controller
{
    public function __construct(private RefundService $refundService)
    {
    }

    /**
     * POST /api/refunds/request
     */
    public function requestRefund(StoreRefundRequest $request)
    {
        try {
            $refundRequest = $this->refundService->requestRefund(
                $request->user()->id,
                $request->validated('ticket_id'),
                $request->validated('reason')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new RefundRequestResource($refundRequest);
    }

    /**
     * GET /api/refunds/status/{id}
     */
    public function getStatus(int $id)
    {
        $refundRequest = RefundRequest::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        return new RefundRequestResource($refundRequest);
    }
}
