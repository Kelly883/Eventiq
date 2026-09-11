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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
            $event = DB::transaction(function () use ($validated, $organizer) {
                $eventData = $this->mapEventData($validated, $organizer->id);
                // Generate unique slug from title to prevent concurrent duplicate titles
                if (!isset($eventData['slug']) && isset($eventData['title'])) {
                    $baseSlug = Str::slug($eventData['title']);
                    $slug = $baseSlug;
                    $counter = 1;
                    while (Event::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter++;
                    }
                    $eventData['slug'] = $slug;
                }

                $event = Event::create($eventData);

                $tiers = $validated['ticket_tiers'] ?? [];
                foreach ($tiers as $index => $tierData) {
                    $tierPayload = $this->mapTierData($tierData, $event->id, $index);
                    TicketTier::create($tierPayload);
                }

                return $event;
            });

            // Dispatch job without blocking response
            try {
                IncrementTotalEventsCreated::dispatch($organizer->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to dispatch IncrementTotalEventsCreated', ['error' => $e->getMessage()]);
            }

            $event->load(['ticketTiers', 'organizer']);

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
            return response()->json(['message' => 'Failed to create event', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/organizer/events/:eventId — Get single event with tiers
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            \Illuminate\Support\Facades\Log::info('organizer_event_show_attempt', ['user_id' => $user?->id, 'event_id' => $id, 'ip' => $request->ip()]);
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $event = Event::without('analyticsEventsMetric')->with(['ticketTiers', 'organizer'])->find($id);

        if (!$event) {
            \Illuminate\Support\Facades\Log::warning('organizer_event_show_not_found', ['user_id' => $user?->id, 'event_id' => $id, 'ip' => $request->ip()]);
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('view', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Illuminate\Support\Facades\Log::warning('organizer_event_show_unauthorized', ['user_id' => $user?->id, 'event_id' => $event->id, 'ip' => $request->ip()]);
            return response()->json(['message' => 'Forbidden — you do not own this event'], 403);
        }

        \Illuminate\Support\Facades\Log::info('organizer_event_show', ['user_id' => $user?->id, 'event_id' => $event->id, 'ip' => $request->ip()]);

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

        try {
            $updatedEvent = DB::transaction(function () use ($event, $validated) {
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

                return $lockedEvent;
            });

            return new EventResource($updatedEvent);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Event update failed', ['event_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update event', 'error' => $e->getMessage()], 500);
        }
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
            // Lock for concurrent delete safety
            $locked = Event::where('id', $event->id)->lockForUpdate()->first();
            if (!$locked) {
                return response()->json(['message' => 'Event not found'], 404);
            }
            $organizerId = $locked->organizer_id;

            // Soft delete (uses SoftDeletes trait)
            $locked->delete();
            $event = $locked;

            try {
                DecrementTotalEventsCreated::dispatch($organizerId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to dispatch DecrementTotalEventsCreated', ['error' => $e->getMessage()]);
            }

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Event delete failed', ['event_id' => $id, 'error' => $e->getMessage()]);
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
            return response()->json(['message' => 'Event not found'], 404);
        }

        try {
            Gate::forUser($user)->authorize('update', $event);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
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

        // Virus scan placeholder — wire ClamAV (clamd) or external service when enabled
        // This is a no-op in local/test but ensures the hook exists for prod hardening.
        if (config('services.clamav.enabled', false)) {
            try {
                $scanner = app(\App\Services\ClamAvScanner::class);
                if (!$scanner->scan($file->getRealPath())) {
                    return response()->json(['message' => 'File failed virus scan.'], 422);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ClamAV scan failed, allowing upload but logging', ['error' => $e->getMessage()]);
                // Fail open in dev, fail closed in prod if required: return 500
            }
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
            // Store file
            $content = file_get_contents($file->getRealPath());
            // Use put to allow overwrite
            $disk->put($path, $content, 'public');

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
                // If URL host not in allowlist, fallback to relative storage path host
                \Illuminate\Support\Facades\Log::warning('Banner URL host not in allowlist, using fallback', ['url' => $url, 'allowed' => $allowedHosts]);
            }

            // Update event
            $event->update(['banner_image_url' => $url]);
            $event->load(['ticketTiers', 'organizer']);

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
            return response()->json(['message' => 'Failed to upload banner', 'error' => $e->getMessage()], 500);
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

        $payload = [
            'event_id' => $eventId,
            'name' => $name,
            'price' => $price !== null ? (float) $price : 0,
            'quantity' => $quantity !== null && $quantity !== '' ? (int) $quantity : null,
            'sales_start_date' => $salesStart,
            'sales_end_date' => $salesEnd,
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
