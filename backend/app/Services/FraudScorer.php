<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FraudScorer
{
    /**
     * Score a transaction for fraud risk (0-100, higher = more risky).
     * Simple rules-based system for v1.
     *
     * SECURITY: This method receives $context from the caller. The caller
     * (CheckoutController) is responsible for populating user_id, email,
     * ip_address, and amount from trusted sources (authenticated user,
     * server-side price computation, trusted proxy layer). This method
     * does NOT read from $request directly to prevent input spoofing.
     */
    public function score(array $context): array
    {
        $score = 0;
        $flags = [];

        // Flag 1: User age < 24 hours
        if (!empty($context['user_id'])) {
            $user = \App\Models\User::find($context['user_id']);
            if ($user && $user->created_at && $user->created_at->diffInHours(now()) < 24) {
                $score += 30;
                $flags[] = 'new_user';
            }
        }

        // Flag 2: High-value order (> $500)
        if (!empty($context['amount']) && (float) $context['amount'] > 500) {
            $score += 20;
            $flags[] = 'high_value';
        }

        // Flag 3: Multiple orders in short time (> 3 in 1 hour)
        if (!empty($context['user_id'])) {
            $orderCount = \App\Features\Checkout\Models\Order::where('user_id', $context['user_id'])
                ->whereNull('deleted_at')
                ->where('created_at', '>', now()->subHour())
                ->count();
            if ($orderCount > 3) {
                $score += 40;
                $flags[] = 'multiple_orders';
            }
        }

        // Flag 4: Different IP from user history
        if (!empty($context['user_id']) && !empty($context['ip_address'])) {
            $lastOrder = \App\Features\Checkout\Models\Order::where('user_id', $context['user_id'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($lastOrder && $lastOrder->ip_address !== $context['ip_address']) {
                $score += 15;
                $flags[] = 'ip_change';
            }
        }

        // Flag 5: Suspicious email patterns
        if (!empty($context['email'])) {
            $email = strtolower($context['email']);
            $suspiciousDomains = ['tempmail', 'throwaway', 'guerrillamail', '10minutemail'];
            foreach ($suspiciousDomains as $domain) {
                if (str_contains($email, $domain)) {
                    $score += 25;
                    $flags[] = 'suspicious_email_domain';
                    break;
                }
            }
        }

        $riskLevel = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');

        Log::info('FraudScorer: Scored transaction', [
            'user_id' => $context['user_id'] ?? null,
            'score' => $score,
            'risk_level' => $riskLevel,
            'flags' => $flags,
        ]);

        return [
            'score' => $score,
            'risk_level' => $riskLevel,
            'flags' => $flags,
            'action' => $this->recommendAction($score, $riskLevel),
        ];
    }

    private function recommendAction(int $score, string $riskLevel): string
    {
        return match ($riskLevel) {
            'high' => 'review',
            'medium' => 'monitor',
            'low' => 'approve',
        };
    }
}
