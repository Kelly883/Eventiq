<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Wraps ticket delivery across email, SMS, and in-app dashboard channels.
 * Used by App\Features\Delivery\Jobs\SendTicketDeliveryJob.
 *
 * Not built on "bam-ticketing-sdk" - see config/ticket-delivery.php for
 * why (that package doesn't exist for PHP). Email uses Laravel's Mail
 * facade directly; SMS uses Termii's REST API via the HTTP client.
 */
class TicketDeliveryService
{
    /**
     * Send a ticket by email. $attachmentPath, if given, should be an
     * absolute path to a generated PDF/ticket file.
     */
    public function sendViaEmail(string $toEmail, string $subject, string $body, ?string $attachmentPath = null): bool
    {
        try {
            Mail::raw($body, function ($message) use ($toEmail, $subject, $attachmentPath) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->from(
                        config('ticket-delivery.email.from_address'),
                        config('ticket-delivery.email.from_name')
                    );

                if ($attachmentPath && is_file($attachmentPath)) {
                    $message->attach($attachmentPath);
                }
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('TicketDeliveryService::sendViaEmail failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Send a ticket notification via SMS (Termii).
     * Docs: https://developers.termii.com/messaging-api
     */
    public function sendViaSms(string $toPhone, string $message): array
    {
        $apiKey = config('ticket-delivery.sms.api_key');

        if (! $apiKey) {
            Log::warning('TicketDeliveryService::sendViaSms skipped - TERMII_API_KEY not configured.');

            return ['sent' => false, 'reason' => 'not_configured'];
        }

        $baseUrl = rtrim(config('ticket-delivery.sms.base_url'), '/');

        try {
            $response = Http::asJson()->post("{$baseUrl}/api/sms/send", [
                'api_key' => $apiKey,
                'to' => $toPhone,
                'from' => config('ticket-delivery.sms.sender_id'),
                'sms' => $message,
                'type' => 'plain',
                'channel' => config('ticket-delivery.sms.channel', 'dnd'),
            ]);

            if ($response->failed()) {
                Log::error('Termii SMS send failed: ' . $response->body());

                return ['sent' => false, 'reason' => 'provider_error'];
            }

            return ['sent' => true, 'message_id' => $response->json('message_id')];
        } catch (\Throwable $e) {
            Log::error('TicketDeliveryService::sendViaSms exception: ' . $e->getMessage());

            return ['sent' => false, 'reason' => 'exception'];
        }
    }

    /**
     * Record an in-app/dashboard delivery notification. Guards against
     * the delivery_events table not having real columns yet (currently
     * just id + timestamps in this repo's migration) - logs and reports
     * unchecked rather than silently writing nothing or crashing.
     */
    public function sendViaDashboard(int $userId, string $ticketReference, array $payload = []): array
    {
        if (! Schema::hasTable('delivery_events') || ! Schema::hasColumn('delivery_events', 'user_id')) {
            Log::warning('TicketDeliveryService::sendViaDashboard skipped - delivery_events table/columns not available yet.', [
                'user_id' => $userId,
                'ticket_reference' => $ticketReference,
            ]);

            return ['recorded' => false, 'checked' => false];
        }

        \App\Features\Delivery\Models\DeliveryEvent::create([
            'user_id' => $userId,
            'ticket_reference' => $ticketReference,
            'channel' => 'dashboard',
            'payload' => $payload,
        ]);

        return ['recorded' => true, 'checked' => true];
    }

    /**
     * Single entry point used by delivery jobs: dispatches to the right
     * channel based on the requested method.
     */
    public function send(string $channel, array $data): array
    {
        return match ($channel) {
            'email' => ['sent' => $this->sendViaEmail(
                $data['to'],
                $data['subject'] ?? 'Your ticket',
                $data['body'] ?? '',
                $data['attachment_path'] ?? null,
            )],
            'sms' => $this->sendViaSms($data['to'], $data['message'] ?? ''),
            'dashboard' => $this->sendViaDashboard($data['user_id'], $data['ticket_reference'], $data['payload'] ?? []),
            default => throw new \InvalidArgumentException("Unknown delivery channel: {$channel}"),
        };
    }

    /**
     * Verify a delivery-status webhook callback. This is a shared secret
     * WE define (via TICKET_DELIVERY_WEBHOOK_SECRET) and expect back from
     * whichever provider calls our webhook URL - not a documented
     * signature scheme, since Termii's public docs don't specify one for
     * SMS delivery reports the way Paystack/Flutterwave do for payments.
     */
    public function verifyWebhookSignature(string $providedSecret): bool
    {
        $configured = config('ticket-delivery.webhook_secret');

        if (! $configured || $providedSecret === '') {
            return false;
        }

        return hash_equals($configured, $providedSecret);
    }
}
