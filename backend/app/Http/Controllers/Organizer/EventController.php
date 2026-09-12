<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Jobs\DecrementTotalEventsCreated;
use App\Jobs\IncrementTotalEventsCreated;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Services\Audit\AuditLogger;
use App\Services\VirusScanning\ScanResult;
use App\Services\VirusScanning\VirusScanner;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    /**
     * GET /api/organizer/events — List organizer's events
     * Paginated 15 per page, filter ?status=draft|published, includes ticketTiers
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $organizer = $this->resolveOrganizer($user);
        if (!$organizer) {
            return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
        }

        Gate::forUser($user)->authorize('viewAny', Event::class);

        $status = $request->query('status');
        if ($status && !in_array($status, ['draft', 'published'], true)) {
            return response()->json([
                'message' => 'The selected status is invalid.',
                'errors' => ['status' => ['Status must be draft or published.']],
            ], 422);
        }

        $query = Event::without('analyticsEventsMetric')
            ->with(['ticketTiers:id,event_id,name,price,quantity,sales_start_date,sales_end_date,tier_order,is_active,currency,status', 'organizer:id,displayName,user_id'])
            ->where('organizer_id', $organizer->id)
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->paginate($perPage);

        // Transform via resource but keep pagination meta
        return EventResource::collection($paginator)->response();
    }

    /**
     * POST /api/organizer/events — Create event with ticket tiers
     */
    public function store(StoreEventRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $organizer = $this->resolveOrganizer($user);
        if (!$organizer) {
            return response()->json(['message' => 'Forbidden — organizer profile required'], 403);
        }

        Gate::forUser($user)->authorize('create', Event::class);

        $validated = $request->validated();

        // Idempotency: if Idempotency-Key header provided, return cached response for duplicate
        $idempotencyKey = $request->header('Idempotency-Key');
        $idempotencyCacheKey = null;
        if ($idempotencyKey) {
            $idempotencyCacheKey = 'event:store:' . $user->id . ':' . sha1($idempotencyKey);
            if ($cached = \Illuminate\Support\Facades\Cache::get($idempotencyCacheKey)) {
                return response()->json($cached, 201);
            }
        }

        try {
            $event = DB::transaction(function () use ($validated, $organizer, $request, $user) {
                $eventData = $this->mapEventData($validated, $organizer->id);
                // Generate unique slug from title.
                // A random 6-char suffix makes collisions astronomically unlikely even under
                // concurrent identical-title creates, so the while(exists) loop is a safety
                // net rather than the primary uniqueness mechanism.
                if (!isset($eventData['slug']) && isset($eventData['title'])) {
                    $baseSlug = Str::slug($eventData['title']);
                    // Try base slug first (clean URLs for unique titles), then add random suffix
                    $slug = $baseSlug;
                    if (Event::where('slug', $slug)->exists()) {
                        $suffix = Str::random(6);
                        $slug = $baseSlug . '-' . $suffix;
                        // Safety net: if by some chance the random suffix collides, append counter
                        $attempt = 1;
                        while (Event::where('slug', $slug)->exists() && $attempt < 50) {
                            $slug = $baseSlug . '-' . $suffix . '-' . $attempt++;
                        }
                    }
                    $eventData['slug'] = $slug;
                }

                $event = Event::create($eventData);

                $tiers = $validated['ticket_tiers'] ?? [];
                foreach ($tiers as $index => $tierData) {
                    $tierPayload = $this->mapTierData($tierData, $event->id, $index);
                    TicketTier::create($tierPayload);
                }

                $event->load(['ticketTiers', 'organizer']);

                AuditLogger::forEvent(
                    action: 'event.created',
                    user: $user,
                    eventId: (string) $event->id,
                    newValues: $event->toArray(),
                    request: $request,
                    description: "Event '{$event->title}' created with " . count($event->ticketTiers) . " ticket tiers"
                );

                return $event;
            });

            $response = (new EventResource($event))->response()->setStatusCode(201);
            // Store idempotency cache for 24h
            if ($idempotencyCacheKey) {
                try {
                    \Illuminate\Support\Facades\Cache::put($idempotencyCacheKey, $response->getData(true), 86400);
                } catch (\Throwable $e) {}
            }
            return $response;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Event store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            AuditLogger::forEvent(
                action: 'event.create_failed',
                user: $user,
                eventId: null,
                request: $request,
                description: 'Failed to create event: ' . $e->getMessage()
            );
            $debug = app()->hasDebugModeEnabled();
            return response()->json([
                'message' => 'Failed to create event',
                'error' => $debug ? $e->getMessage() : 'An internal error occurred.',
            ], 500);
        }
    }

    /**
     * GET /api/organizer/events/:eventId — Get single event with tiers
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            AuditLogger::forEvent(
                action: 'event.show_attempt',
                user: $user,
                eventId: (string) $id,
                request: $request,
                description: 'Unauthenticated attempt to view event'
            );
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $event = Event::without('analyticsEventsMetric')->with(['ticketTiers', 'organizer'])->find($id);

        if (!$event) {
            AuditLogger::forEvent(
                action: 'event.show_not_found',
                user: $user,
                eventId: (string) $id,
                request: $request,
                description: 'Attempted to view non-existent event'
            );
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            AuditLogger::forEvent(
                action: 'event.show_unauthorized',
                user: $user,
                eventId: (string) $event->id,
                request: $request,
                description: 'Unauthorized attempt to view event'
            );
            return response()->json(['message' => 'Forbidden — you do not own this event'], 403);
        }

        AuditLogger::forEvent(
            action: 'event.viewed',
            user: $user,
            eventId: (string) $event->id,
            request: $request,
            description: "Event '{$event->title}' viewed"
        );

        return new EventResource($event);
    }

    /**
     * PATCH /api/organizer/events/:eventId — Update event and tiers
     * Handles create / update / delete of tiers in one transaction
     */
    public function update(UpdateEventRequest $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $event = Event::without('analyticsEventsMetric')->with('ticketTiers')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('update', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you do not own this event'], 403);
        }

        $validated = $request->validated();

        $maxRetries = 3;
        $retryDelay = 0.1; // 100ms

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $updatedEvent = DB::transaction(function () use ($event, $validated, $user, $request) {
                    // Lock event row for concurrent update safety
                    $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->firstOrFail();
                    // Update event fields if present
                    $eventData = $this->mapEventData($validated, $lockedEvent->organizer_id, true);
                    if (!empty($eventData)) {
                        $lockedEvent->update($eventData);
                    }

                    // Handle ticket tiers if provided
                    if (array_key_exists('ticket_tiers', $validated)) {
                        $incomingTiers = $validated['ticket_tiers'] ?? [];
                        // Lock tiers for this event to prevent race
                        $existingTiers = TicketTier::where('event_id', $lockedEvent->id)->lockForUpdate()->get()->keyBy('id');
                        $keepIds = [];

                        foreach ($incomingTiers as $index => $tierData) {
                            // Normalize alternative keys
                            if (isset($tierData['salesStartDate']) && !isset($tierData['sales_start_date'])) {
                                $tierData['sales_start_date'] = $tierData['salesStartDate'];
                            }
                            if (isset($tierData['salesEndDate']) && !isset($tierData['sales_end_date'])) {
                                $tierData['sales_end_date'] = $tierData['salesEndDate'];
                            }

                            $tierId = $tierData['id'] ?? null;

                            if ($tierId && $existingTiers->has($tierId)) {
                                // Update existing tier — ensure it belongs to this event
                                $tier = $existingTiers->get($tierId);
                                if ((int) $tier->event_id !== (int) $lockedEvent->id) {
                                    throw new \Illuminate\Validation\ValidationException(
                                        validator([], []),
                                        response()->json(['message' => 'Ticket tier does not belong to this event'], 422)
                                    );
                                }
                                $payload = $this->mapTierData($tierData, $lockedEvent->id, $index, true);
                                unset($payload['event_id']); // don't change FK
                                $tier->update($payload);
                                $keepIds[] = $tierId;
                            } else {
                                // Create new tier
                                $payload = $this->mapTierData($tierData, $lockedEvent->id, $index);
                                $newTier = TicketTier::create($payload);
                                $keepIds[] = $newTier->id;
                            }
                        }

                        // Delete tiers not in incoming payload
                        $toDelete = $existingTiers->keys()->diff($keepIds);
                        if ($toDelete->isNotEmpty()) {
                            TicketTier::whereIn('id', $toDelete->toArray())
                                ->where('event_id', $lockedEvent->id)
                                ->delete(); // soft delete if trait
                        }
                    }

                    $lockedEvent->refresh();
                    $lockedEvent->load(['ticketTiers', 'organizer']);

                    AuditLogger::forEvent(
                        action: 'event.updated',
                        user: $user,
                        eventId: (string) $lockedEvent->id,
                        oldValues: $event->toArray(),
                        newValues: $lockedEvent->toArray(),
                        request: $request,
                        description: "Event '{$lockedEvent->title}' updated"
                    );

                    return $lockedEvent;
                });

                return new EventResource($updatedEvent);
            } catch (\Illuminate\Database\QueryException $e) {
                // Retry on deadlock or serialization failure
                $sqlState = $e->getCode();
                $isDeadlock = in_array($sqlState, ['40001', 'serialization_failure'], true);
                $isLockWaitTimeout = $sqlState === 'HY000' && str_contains($e->getMessage(), 'lock');

                if (($isDeadlock || $isLockWaitTimeout) && $attempt < $maxRetries - 1) {
                    \Illuminate\Support\Facades\Log::warning('Deadlock detected, retrying', [
                        'event_id' => $event->id,
                        'attempt' => $attempt + 1,
                        'error' => $e->getMessage(),
                    ]);
                    usleep((int) ($retryDelay * 1000000));
                    $retryDelay *= 2; // exponential backoff

                    continue;
                }

                throw $e;
            }
        }

        // This should never be reached, but just in case
        return response()->json(['message' => 'Failed to update event'], 500);
    }

    /**
     * DELETE /api/organizer/events/:eventId — Soft delete
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $event = Event::without('analyticsEventsMetric')->find($id);

        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('delete', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'Forbidden — you do not own this event'], 403);
        }

        try {
            $deleted = DB::transaction(function () use ($event, $user, $request) {
                // Lock for concurrent delete safety
                $locked = Event::where('id', $event->id)->lockForUpdate()->first();
                if (!$locked) {
                    return null;
                }
                $organizerId = $locked->organizer_id;

                // Soft delete (uses SoftDeletes trait)
                $locked->delete();

                AuditLogger::forEvent(
                    action: 'event.deleted',
                    user: $user,
                    eventId: (string) $locked->id,
                    oldValues: $locked->toArray(),
                    request: $request,
                    description: "Event '{$locked->title}' soft deleted"
                );

                return $organizerId;
            });

            if ($deleted === null) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            try {
                DecrementTotalEventsCreated::dispatch($deleted);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to dispatch DecrementTotalEventsCreated', ['error' => $e->getMessage()]);
            }

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Event delete failed', ['event_id' => $id, 'error' => $e->getMessage()]);
            AuditLogger::forEvent(
                action: 'event.delete_failed',
                user: $user,
                eventId: (string) $event->id,
                request: $request,
                description: 'Failed to delete event: ' . $e->getMessage()
            );
            return response()->json(['message' => 'Failed to delete event'], 500);
        }
    }

    /**
     * POST /api/organizer/events/:eventId/upload-banner — Upload banner image
     */
    public function uploadBanner(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $event = Event::without('analyticsEventsMetric')->find($id);

        if (!$event) {
            AuditLogger::forEvent(
                action: 'event.banner_upload_not_found',
                user: $user,
                eventId: (string) $id,
                request: $request,
                description: 'Banner upload attempted for non-existent event'
            );
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('update', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            AuditLogger::forEvent(
                action: 'event.banner_upload_unauthorized',
                user: $user,
                eventId: (string) $event->id,
                request: $request,
                description: 'Unauthorized banner upload attempt'
            );
            return response()->json(['message' => 'Forbidden — you do not own this event'], 403);
        }

        // Validate file - also check actual image content via getimagesize
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'banner' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            // Also support field name 'banner_image' for compat
            'banner_image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ], [
            'banner.required' => 'Banner image is required.',
            'banner.image' => 'File must be an image.',
            'banner.mimes' => 'Invalid file type. Only jpg, png, gif, webp allowed.',
            'banner.max' => 'File too large. Maximum size is 5MB.',
        ]);

        if ($validator->fails()) {
            $isTooLarge = collect($validator->errors()->all())->contains(fn ($msg) => str_contains(strtolower($msg), 'too large') || str_contains(strtolower($msg), '5mb'));
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], $isTooLarge ? 413 : 422);
        }

        $file = $request->file('banner') ?? $request->file('banner_image') ?? $request->file('image');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'Invalid file upload'], 422);
        }

        // Extra size check (in bytes) to ensure 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['message' => 'File too large. Maximum size is 5MB.'], 413);
        }

        // Validate actual image content (not just extension) to prevent malicious upload with spoofed mime
        $imageInfo = @getimagesize($file->getRealPath());
        if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return response()->json(['message' => 'Invalid image content. File is not a valid image.'], 422);
        }

        // Virus scan - uses ClamAV if available, falls back to basic validation
        try {
            $virusScanner = new VirusScanner();
            $scanResult = $virusScanner->scan($file);

            // Fail closed: if scanner is unavailable, reject the upload rather than
            // silently accepting untrusted files when ClamAV/VirusTotal is down.
            if ($scanResult->scannerEngine === 'unavailable') {
                \Illuminate\Support\Facades\Log::warning('Virus scan unavailable, rejecting upload', [
                    'event_id' => $id,
                    'user_id' => $user->id,
                    'reason' => $scanResult->rawOutput,
                ]);

                return response()->json([
                    'message' => 'Security scan unavailable. Upload rejected.',
                    'error' => $scanResult->rawOutput,
                ], 503);
            }

            if (!$scanResult->isClean) {
                \Illuminate\Support\Facades\Log::warning('Virus scan detected threat', [
                    'event_id' => $id,
                    'user_id' => $user->id,
                    'threat' => $scanResult->threatName,
                    'scanner' => $scanResult->scannerEngine,
                ]);

                return response()->json([
                    'message' => 'File failed security scan. Upload rejected.',
                    'error' => $scanResult->threatName,
                ], 422);
            }

            \Illuminate\Support\Facades\Log::info('Virus scan passed', [
                'event_id' => $id,
                'user_id' => $user->id,
                'scanner' => $scanResult->scannerEngine,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Virus scan failed, rejecting upload', [
                'event_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Security scan failed. Upload rejected.',
            ], 503);
        }
        // Validate Mime by actual content via finfo, not just extension
        $mime = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $extByMime = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            // Try to allow mime from extension if finfo is ambiguous
            $guessedExt = strtolower($file->getClientOriginalExtension());
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($guessedExt, $allowedExts, true)) {
                return response()->json(['message' => 'Invalid file type. Only jpg, png, gif, webp allowed.'], 422);
            }
            $mime = $mime ?? 'image/jpeg';
        }

        $ext = $extByMime[$mime] ?? strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        // Ensure ext is allowed
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $ext = 'jpg';
        }
        if ($ext === 'jpeg') $ext = 'jpg';

        // Versioned path to avoid CDN caching race and concurrent overwrite
        $path = "events/{$event->id}/banner_" . time() . "_" . Str::random(6) . ".{$ext}";

        // Choose disk: s3 if configured, else public
        $disk = $this->resolveDisk();

        $oldUrl = $event->banner_image_url;

        try {
            // Stream file to storage instead of loading entire file into memory
            $stream = fopen($file->getRealPath(), 'rb');
            if ($stream === false) {
                return response()->json(['message' => 'Failed to read uploaded file'], 500);
            }
            // Use put to allow overwrite; close stream after write
            $disk->put($path, $stream, 'public');
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Generate URL
            try {
                $url = $disk->url($path);
            } catch (\Throwable $e) {
                $url = Storage::url($path);
            }

            // Fallback if url doesn't look like URL, prepend APP_URL/storage
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $url = rtrim(config('app.url') ?? env('APP_URL', 'http://localhost'), '/') . '/storage/' . ltrim($path, '/');
            }

            // Allowlist check: ensure generated URL is from our storage host (prevent external URL injection)
            $allowedHosts = array_filter([
                parse_url(config('app.url'), PHP_URL_HOST),
                parse_url(config('filesystems.disks.s3.url') ?? '', PHP_URL_HOST),
                parse_url(config('filesystems.disks.s3.endpoint') ?? '', PHP_URL_HOST),
                parse_url(env('AWS_URL', ''), PHP_URL_HOST),
            ]);
            $urlHost = parse_url($url, PHP_URL_HOST);
            if (!empty($allowedHosts) && $urlHost && !in_array($urlHost, $allowedHosts, true)) {
                \Illuminate\Support\Facades\Log::warning('Banner URL host not in allowlist, rejecting upload', [
                    'url' => $url,
                    'allowed' => $allowedHosts,
                    'event_id' => $id,
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'message' => 'Failed to upload banner',
                    'error' => 'Invalid storage host.',
                ], 500);
            }

            // Update event
            $event->update(['banner_image_url' => $url]);
            $event->load(['ticketTiers', 'organizer']);

            AuditLogger::forEvent(
                action: 'event.banner_uploaded',
                user: $user,
                eventId: (string) $event->id,
                newValues: ['banner_image_url' => $url],
                request: $request,
                description: "Banner uploaded for event '{$event->title}'"
            );

            // Optionally delete old if different path
            if ($oldUrl && $oldUrl !== $url) {
                $oldPath = $this->extractStoragePath($oldUrl);
                if ($oldPath && $oldPath !== $path) {
                    try { $disk->delete($oldPath); } catch (\Throwable $e) {}
                    if ($disk->getConfig()['driver'] !== 's3') {
                        try { Storage::disk('s3')->delete($oldPath); } catch (\Throwable $e) {}
                    }
                }
            }

            return new EventResource($event);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Banner upload failed', ['event_id' => $id, 'error' => $e->getMessage()]);
            $debug = app()->hasDebugModeEnabled();
            return response()->json([
                'message' => 'Failed to upload banner',
                'error' => $debug ? $e->getMessage() : 'An internal error occurred.',
            ], 500);
        }
    }

    /**
     * Resolve organizer for user (handles both user_id and userId columns)
     */
    private function resolveOrganizer($user): ?Organizer
    {
        // Prefer relationship
        $organizer = $user->organizer;
        if ($organizer) {
            return $organizer;
        }
        // Fallback query
        return Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->first();
    }

    /**
     * Map validated data to event attributes
     */
    private function mapEventData(array $validated, $organizerId, bool $isUpdate = false): array
    {
        $map = [];

        if (isset($validated['title'])) {
            $map['title'] = $validated['title'];
        }
        if (array_key_exists('description', $validated)) {
            $map['description'] = $validated['description'];
        }
        if (isset($validated['category'])) {
            $map['category'] = $validated['category'];
        }
        if (isset($validated['start_datetime'])) {
            $map['start_datetime'] = $validated['start_datetime'];
        }
        if (isset($validated['end_datetime'])) {
            $map['end_datetime'] = $validated['end_datetime'];
        }
        if (isset($validated['venue_name'])) {
            $map['venue_name'] = $validated['venue_name'];
        }
        if (array_key_exists('venue_address', $validated)) {
            $map['venue_address'] = $validated['venue_address'];
        }
        if (isset($validated['capacity'])) {
            $map['capacity'] = (int) $validated['capacity'];
        }
        if (isset($validated['status'])) {
            $map['status'] = $validated['status'];
        }
        if (array_key_exists('is_public', $validated)) {
            $map['is_public'] = (bool) $validated['is_public'];
        } elseif (array_key_exists('isPublic', $validated)) {
            $map['is_public'] = (bool) $validated['isPublic'];
        }
        // banner_image_url only via upload-banner, ignore direct payload to prevent external URL injection
        // if (isset($validated['banner_image_url'])) { $map['banner_image_url'] = $validated['banner_image_url']; }
        // For create, ensure required organizer linkage
        if (!$isUpdate) {
            $map['organizer_id'] = $organizerId;
            // Legacy user_id column for compatibility if needed
            // Try to set user_id from organizer's user_id
            try {
                $organizer = Organizer::find($organizerId);
                if ($organizer && isset($organizer->user_id)) {
                    $map['user_id'] = $organizer->user_id;
                } elseif ($organizer && isset($organizer->userId)) {
                    $map['user_id'] = $organizer->userId;
                }
            } catch (\Throwable $e) {}
        }

        return $map;
    }

    /**
     * Map tier data to model attributes
     */
    private function mapTierData(array $tierData, $eventId, int $index = 0, bool $isUpdate = false): array
    {
        // Handle camelCase inside tier
        $name = $tierData['name'] ?? null;
        $price = $tierData['price'] ?? $tierData['amount'] ?? null;
        $quantity = $tierData['quantity'] ?? $tierData['capacity'] ?? null;
        $salesStart = $tierData['sales_start_date'] ?? $tierData['salesStartDate'] ?? null;
        $salesEnd = $tierData['sales_end_date'] ?? $tierData['salesEndDate'] ?? null;
        $earlyBirdPrice = $tierData['early_bird_price'] ?? $tierData['earlyBirdPrice'] ?? null;
        $earlyBirdEndDate = $tierData['early_bird_end_date'] ?? $tierData['earlyBirdEndDate'] ?? null;

        $payload = [
            'event_id' => $eventId,
            'name' => $name,
            'price' => $price !== null ? (float) $price : 0,
            'quantity' => $quantity !== null && $quantity !== '' ? (int) $quantity : null,
            'sales_start_date' => $salesStart,
            'sales_end_date' => $salesEnd,
            'early_bird_price' => $earlyBirdPrice !== null ? (float) $earlyBirdPrice : null,
            'early_bird_end_date' => $earlyBirdEndDate,
            'tier_order' => $tierData['tier_order'] ?? $index,
            'is_active' => $tierData['is_active'] ?? true,
            'currency' => $tierData['currency'] ?? 'NGN',
            'status' => $tierData['status'] ?? 'published',
        ];

        // Remove nulls that shouldn't overwrite on update? Keep for create
        if ($isUpdate) {
            // On update, allow partial but keep existing if not provided
            // Filter out null quantity handling above already
        }

        // Ensure id not mass assigned on create
        if (isset($tierData['id']) && !$isUpdate) {
            unset($payload['id']);
        }

        return $payload;
    }

    private function resolveDisk()
    {
        try {
            $s3Configured = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'));
            return $s3Configured ? Storage::disk('s3') : Storage::disk('public');
        } catch (\Throwable $e) {
            return Storage::disk('public');
        }
    }

    private function extractStoragePath(?string $url): ?string
    {
        if (!$url) return null;
        if (str_starts_with($url, 'events/')) {
            return $url;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) return null;
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $pos = strpos($path, 'events/');
        return $pos !== false ? substr($path, $pos) : null;
    }
}
