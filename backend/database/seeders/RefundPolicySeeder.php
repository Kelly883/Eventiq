<?php

namespace Database\Seeders;

use App\Features\Refunds\Enums\RefundMethodEnum;
use App\Features\Refunds\Models\RefundPolicy;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Seeder;

class RefundPolicySeeder extends Seeder
{
    public function run(): void
    {
        // Get an organizer and event to associate policies with
        $organizer = Organizer::firstOrCreate(
            ['name' => 'Default Organizer'],
            ['user_id' => 1]
        );

        // Create a few events with different refund policies
        $events = Event::factory()->count(3)->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
        ]);

        // Policy 1: Standard 14-day refund window, full refund before event, 50% after
        $events[0]->refundPolicies()->create([
            'refund_window_days' => 14,
            'refund_percentage_before_event' => 100.00,
            'refund_percentage_after_event_start' => 50.00,
            'allow_refunds_after_event_start' => false,
            'processing_time_business_days' => 3,
            'allowed_refund_methods' => [
                RefundMethodEnum::ORIGINAL_PAYMENT_METHOD->value,
                RefundMethodEnum::STORE_CREDIT->value,
            ],
            'requires_approval' => true,
            'auto_approve_threshold' => null,
            'cancellation_policy' => 'Refunds allowed up to 14 days before event',
        ]);

        // Policy 2: 7-day refund window, no refunds after event starts
        $events[1]->refundPolicies()->create([
            'refund_window_days' => 7,
            'refund_percentage_before_event' => 100.00,
            'refund_percentage_after_event_start' => 0.00,
            'allow_refunds_after_event_start' => false,
            'processing_time_business_days' => 5,
            'allowed_refund_methods' => [
                RefundMethodEnum::ORIGINAL_PAYMENT_METHOD->value,
            ],
            'requires_approval' => false,
            'auto_approve_threshold' => 50.00,
            'cancellation_policy' => 'Refunds allowed up to 7 days before event',
        ]);

        // Policy 3: Flexible 21-day refund window, 80% before event, 30% after
        $events[2]->refundPolicies()->create([
            'refund_window_days' => 21,
            'refund_percentage_before_event' => 80.00,
            'refund_percentage_after_event_start' => 30.00,
            'allow_refunds_after_event_start' => true,
            'processing_time_business_days' => 7,
            'allowed_refund_methods' => [
                RefundMethodEnum::ORIGINAL_PAYMENT_METHOD->value,
                RefundMethodEnum::STORE_CREDIT->value,
                RefundMethodEnum::ALTERNATIVE_PAYMENT_METHOD->value,
            ],
            'requires_approval' => false,
            'auto_approve_threshold' => 100.00,
            'cancellation_policy' => 'Refunds allowed up to 21 days before event',
        ]);
    }
}
