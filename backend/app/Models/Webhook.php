<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organizer_id',
        'url',
        'description',
        'secret',
        'subscribed_events',
        'status',
        'last_failure_at',
        'last_success_at',
        'failure_count',
        'timeout_seconds',
        'retry_policy',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'retry_policy' => 'array',
        'last_failure_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class);
    }

    public static function generateSecret(): string
    {
        return hash('sha256', Str::random(32));
    }

    public function isValidSignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function hasSubscribed(string $event): bool
    {
        return in_array($event, $this->subscribed_events ?? [], true);
    }

    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function isHttpError(int $statusCode): bool
    {
        $policy = $this->retry_policy ?? [];
        $retryable = $policy['retryable_status_codes'] ?? [408, 429, 500, 502, 503, 504];

        return ! in_array($statusCode, $retryable, true) || $statusCode >= 400;
    }

    public function recordSuccess(): void
    {
        $this->update([
            'status' => 'active',
            'failure_count' => 0,
            'last_success_at' => now(),
            'last_failure_at' => null,
        ]);
    }

    public function recordFailure(string $errorMessage): void
    {
        $newCount = $this->failure_count + 1;
        $retryPolicy = $this->retry_policy ?? [];
        $maxFailures = $retryPolicy['max_failures'] ?? 10;

        $this->update([
            'failure_count' => $newCount,
            'last_failure_at' => now(),
            'status' => $newCount >= $maxFailures ? 'failed' : 'active',
        ]);
    }

    public function scopeForOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->whereJsonContains('subscribed_events', $event);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCreatedBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function testEndpoint(array $payload = []): array
    {
        $payload = empty($payload) ? ['test' => true, 'timestamp' => now()->toIso8601String()] : $payload;
        $signature = hash_hmac('sha256', json_encode($payload), $this->secret);

        try {
            $response = Http::timeout($this->timeout_seconds ?? 30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'test',
                ])
                ->post($this->url, $payload);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'error' => $response->failed() ? $response->body() : null,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'status_code' => null,
                'response_body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
