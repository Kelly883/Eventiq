<?php

namespace App\Features\Refunds\Controllers;

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Requests\StoreRefundRequest;
use App\Features\Refunds\Resources\RefundRequestResource;
use App\Features\Refunds\Services\RefundService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    public function __construct(private RefundService $refundService)
    {
    }

    /**
     * POST /api/refunds/request
     */
    public function requestRefund(StoreRefundRequest $request): JsonResponse
    {
        try {
            $refundRequest = $this->refundService->requestRefund(
                $request->user()->id,
                $request->validated('ticket_id'),
                $request->validated('reason'),
                $request->validated('refund_method', 'original_payment'),
                $request->validated('explanation')
            );
        } catch (\RuntimeException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;
            return response()->json(['message' => $e->getMessage()], $code);
        }

        return (new RefundRequestResource($refundRequest))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/refunds/status/{id}
     */
    public function getStatus(string $id)
    {
        $refundRequest = RefundRequest::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        return new RefundRequestResource($refundRequest);
    }
}
