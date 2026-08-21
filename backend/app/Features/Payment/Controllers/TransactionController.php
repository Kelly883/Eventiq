<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Resources\TransactionResource;
use App\Features\Payment\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController
{
    public function history(Request $request)
    {
        $user = $request->user();

        $transactions = Transaction::forUser($user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => TransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $transaction = Transaction::forUser($request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(new TransactionResource($transaction));
    }
}
