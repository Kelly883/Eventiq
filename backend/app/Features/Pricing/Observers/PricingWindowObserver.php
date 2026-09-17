<?php

namespace App\Features\Pricing\Observers;

use App\Features\Compliance\Enums\AuditLogAction;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\User;
use App\Services\Audit\AuditLogger;

class PricingWindowObserver
{
    public function created(PricingWindow $window): void
    {
        $user = $this->currentUser();
        if (!$user) {
            return;
        }

        AuditLogger::log(
            action: AuditLogAction::PRICING_WINDOW_CREATED,
            user: $user,
            resourceType: 'pricing_window',
            resourceId: (string) $window->id,
            newValues: $window->toArray(),
            description: "Pricing window '{$window->window_name}' created for event {$window->event_id}"
        );
    }

    public function updated(PricingWindow $window): void
    {
        $user = $this->currentUser();
        if (!$user) {
            return;
        }

        $oldValues = $window->getOriginal();
        AuditLogger::log(
            action: AuditLogAction::PRICING_WINDOW_UPDATED,
            user: $user,
            resourceType: 'pricing_window',
            resourceId: (string) $window->id,
            oldValues: $oldValues,
            newValues: $window->fresh()->toArray(),
            description: "Pricing window '{$window->window_name}' updated for event {$window->event_id}"
        );
    }

    public function deleted(PricingWindow $window): void
    {
        $user = $this->currentUser();
        if (!$user) {
            return;
        }

        AuditLogger::log(
            action: AuditLogAction::PRICING_WINDOW_DELETED,
            user: $user,
            resourceType: 'pricing_window',
            resourceId: (string) $window->id,
            oldValues: $window->toArray(),
            description: "Pricing window '{$window->window_name}' deleted from event {$window->event_id}"
        );
    }

    public function restored(PricingWindow $window): void
    {
        $user = $this->currentUser();
        if (!$user) {
            return;
        }

        AuditLogger::log(
            action: AuditLogAction::PRICING_WINDOW_RESTORED,
            user: $user,
            resourceType: 'pricing_window',
            resourceId: (string) $window->id,
            newValues: $window->fresh()->toArray(),
            description: "Pricing window '{$window->window_name}' restored for event {$window->event_id}"
        );
    }

    private function currentUser(): ?User
    {
        $request = request();
        return $request?->user();
    }
}
