<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Models\PaymentMethod;
use App\Features\Payment\Resources\PaymentMethodResource;
use App\Features\Payment\Requests\StorePaymentMethodRequest;
use App\Features\Payment\Requests\UpdatePaymentMethodRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentMethodController
{
    public function index(Request $request)
    {
        $methods = PaymentMethod::forUser($request->user()->id)
            ->active()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => PaymentMethodResource::collection($methods),
        ]);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $data = $request->validated();

        return DB::transaction(function () use ($request, $data) {
            $user = $request->user();

            if (! empty($data['is_default'])) {
                PaymentMethod::forUser($user->id)
                    ->where('gateway', $data['gateway'])
                    ->whereNull('deleted_at')
                    ->update(['is_default' => false]);
            }

            $method = $user->paymentMethods()->create($data);

            return (new PaymentMethodResource($method))
                ->response()
                ->setStatusCode(201);
        });
    }

    public function show(Request $request, string $id)
    {
        $method = PaymentMethod::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(new PaymentMethodResource($method));
    }

    public function update(UpdatePaymentMethodRequest $request, string $id)
    {
        $method = PaymentMethod::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validated();

        return DB::transaction(function () use ($method, $data) {
            if (! empty($data['is_default'])) {
                PaymentMethod::forUser($method->user_id)
                    ->where('gateway', $method->gateway)
                    ->where('id', '!=', $method->id)
                    ->whereNull('deleted_at')
                    ->update(['is_default' => false]);
            }

            $method->update($data);

            return response()->json(new PaymentMethodResource($method));
        });
    }

    public function destroy(Request $request, string $id)
    {
        $method = PaymentMethod::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $method->delete();

        return response()->noContent();
    }

    public function setDefault(Request $request, string $id)
    {
        $method = PaymentMethod::forUser($request->user()->id)
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        return DB::transaction(function () use ($method) {
            PaymentMethod::forUser($method->user_id)
                ->where('gateway', $method->gateway)
                ->where('id', '!=', $method->id)
                ->whereNull('deleted_at')
                ->update(['is_default' => false]);

            $method->update(['is_default' => true]);

            return response()->json(new PaymentMethodResource($method));
        });
    }
}
