<?php

namespace App\Features\OrganizerProfile\Controllers;

use App\Http\Resources\OrganizerPublicResource;
use App\Http\Resources\OrganizerPrivateResource;
use App\Models\Organizer;
use App\Models\Event;
use App\Features\OrganizerProfile\Requests\UpdateOrganizerProfileRequest;
use App\Features\Compliance\Services\AuditLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OrganizerProfileController extends Controller
{
    public function __construct(private AuditLogService $auditLogService) {}

    /**
     * GET /api/organizers/:organizerId — Public, isPublic check, getPublicProfile
     * Rate limit 20/min per IP
     */
    public function show(Request $request, $id)
    {
        $organizer = Organizer::with('user')->find($id);

        if (!$organizer) {
            return response()->json(['message' => 'Organizer not found'], 404);
        }

        // isPublic false and requester is not the organizer → 404 (don't reveal)
        if (!$organizer->isPublic) {
            $user = $request->user();
            $ownerId = $organizer->user_id ?? $organizer->userId;
            if (!$user || $user->id !== $ownerId) {
                return response()->json(['message' => 'Organizer not found'], 404);
            }
        }

        return response()->json([
            'data' => $organizer->getPublicProfile(),
        ], Response::HTTP_OK);
    }

    /**
     * GET /api/organizers/:organizerId/events — Public, pagination, status filter
     * Query: status=upcoming|past, limit=20 max 100, offset=0
     * Rate limit 20/min per IP
     */
    public function events(Request $request, $id)
    {
        $organizer = Organizer::find($id);
        if (!$organizer) {
            return response()->json(['message' => 'Organizer not found'], 404);
        }

        if (!$organizer->isPublic) {
            $user = $request->user();
            $ownerId = $organizer->user_id ?? $organizer->userId;
            if (!$user || $user->id !== $ownerId) {
                return response()->json(['message' => 'Organizer not found'], 404);
            }
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:upcoming,past',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);
        $status = $validated['status'] ?? null;

        $query = Event::where('organizer_id', $organizer->id);

        if ($status === 'upcoming') {
            $query->where('start_datetime', '>', now());
        } elseif ($status === 'past') {
            $query->where('start_datetime', '<=', now());
        }

        $total = (clone $query)->count();
        $events = $query->orderByDesc('start_datetime')->limit($limit)->offset($offset)->get();

        // Map to spec shape
        $mapped = $events->map(function (Event $event) {
            // ticketsAvailable/sold from capacity and tickets relation if exists
            $ticketsAvailable = $event->capacity ?? 0;
            $ticketsSold = 0;
            try {
                if (method_exists($event, 'tickets')) {
                    $ticketsSold = $event->tickets()->count();
                    $ticketsAvailable = max(0, ($event->capacity ?? 0) - $ticketsSold);
                }
            } catch (\Throwable $e) {
                // ignore
            }
            return [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->start_datetime ?? $event->created_at,
                'description' => $event->description,
                'thumbnailUrl' => $event->banner_image_url ?? null,
                'ticketsAvailable' => $ticketsAvailable,
                'ticketsSold' => $ticketsSold,
            ];
        });

        return response()->json([
            'events' => $mapped,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * GET /api/organizers/me — Authenticated, Organizer role, getPrivateProfile
     * 401 if not auth, 403 if not organizer, 404 if no profile
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check Organizer role via hasRole or OrganizerPolicy
        $isOrganizer = $user->hasRole('organizer') || $user->hasRole('Organizer') || Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->exists();
        // Also allow admin to view? Spec says 403 if not organizer, so check
        if (!$isOrganizer) {
            // Try policy
            $hasOrganizerRole = $user->roles()->whereIn('name', ['organizer', 'Organizer'])->exists();
            if (!$hasOrganizerRole) {
                return response()->json(['message' => 'Forbidden — organizer role required'], 403);
            }
        }

        $organizer = Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->first();
        if (!$organizer) {
            return response()->json(['message' => 'Organizer profile not found'], 404);
        }

        return response()->json([
            'data' => $organizer->getPrivateProfile(),
            'updatedAt' => $organizer->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * PATCH /api/organizers/me/update — Authenticated, validate, audit, 5/min per user
     */
    public function update(UpdateOrganizerProfileRequest $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $isOrganizer = $user->hasRole('organizer') || $user->hasRole('Organizer') || Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->exists();
        if (!$isOrganizer) {
            $hasOrganizerRole = $user->roles()->whereIn('name', ['organizer', 'Organizer'])->exists();
            if (!$hasOrganizerRole) {
                return response()->json(['message' => 'Forbidden — organizer role required'], 403);
            }
        }

        $organizer = Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->first();
        if (!$organizer) {
            return response()->json(['message' => 'Organizer profile not found'], 404);
        }

        $validated = $request->validated();
        $oldValues = $organizer->getPrivateProfile();

        try {
            $organizer->update($validated);
            $organizer->refresh();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
        }

        $newValues = $organizer->getPrivateProfile();

        // Audit log
        try {
            $this->auditLogService->log('profile_updated', 'organizer', $organizer->id, [
                'oldValue' => $oldValues,
                'newValue' => $newValues,
                'updated_fields' => array_keys($validated),
            ], $user->id);
        } catch (\Throwable $e) {
            // don't fail update if audit fails
        }

        return response()->json([
            'data' => $newValues,
            'message' => 'Profile updated successfully.',
        ]);
    }

    /**
     * Legacy PUT /organizer/profile for backward compat — delegates to update
     */
    public function edit()
    {
        $organizer = Organizer::where('user_id', auth()->id())->orWhere('userId', auth()->id())->firstOrFail();
        return response()->json([
            'data' => new OrganizerPrivateResource($organizer),
        ]);
    }

    /**
     * POST /api/organizers/me/upload-avatar — Auth, MIME, 5MB, S3, resize 400x400, delete old, audit, 10/min
     */
    public function uploadAvatar(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $isOrganizer = $user->hasRole('organizer') || $user->hasRole('Organizer') || Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->exists();
        if (!$isOrganizer) {
            $hasOrganizerRole = $user->roles()->whereIn('name', ['organizer', 'Organizer'])->exists();
            if (!$hasOrganizerRole) {
                return response()->json(['message' => 'Forbidden — organizer role required'], 403);
            }
        }

        $organizer = Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->first();
        if (!$organizer) {
            return response()->json(['message' => 'Organizer profile not found'], 404);
        }

        // Validate file — handle 413 for too large explicitly
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'avatar' => 'required|file|mimes:jpeg,png,webp|max:5120', // 5MB = 5120 KB
        ], [
            'avatar.max' => 'File too large. Maximum size is 5MB.',
            'avatar.mimes' => 'Invalid file type. Only jpeg, png, webp allowed.',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $isTooLarge = $errors->has('avatar') && str_contains(implode(' ', $errors->get('avatar')), 'too large');
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $errors,
            ], $isTooLarge ? 413 : 400);
        }

        $file = $request->file('avatar');
        if (!$file || !$file->isValid()) {
            return response()->json(['message' => 'Invalid file upload'], 400);
        }

        // Check MIME explicitly (finfo)
        $mime = $file->getMimeType();
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            return response()->json(['message' => 'Invalid file type. Only jpeg, png, webp allowed.'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['message' => 'File too large. Maximum size is 5MB.'], 413);
        }

        try {
            $ext = $file->getClientOriginalExtension() ?: match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $userId = $user->id;
            $timestamp = time();
            $path = "avatars/{$userId}/{$timestamp}.{$ext}";

            // Resize to 400x400 using GD (sharp equivalent)
            $imageContent = file_get_contents($file->getRealPath());
            $resizedContent = $this->resizeImageTo400($imageContent, $mime);

            // Choose disk: s3 if configured, else public (so tests don't need AWS)
            $disk = null;
            try {
                $s3Configured = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'));
                $disk = $s3Configured ? Storage::disk('s3') : Storage::disk('public');
            } catch (\Throwable $e) {
                $disk = Storage::disk('public');
            }

            $oldAvatarUrl = $organizer->avatarUrl;

            // Store resized image
            $disk->put($path, $resizedContent, 'public');

            // Generate URL — for s3, Storage::url will give S3 URL; for public, give /storage/...
            try {
                $avatarUrl = $disk->url($path);
            } catch (\Throwable $e) {
                $avatarUrl = $path;
            }

            $organizer->update(['avatarUrl' => $avatarUrl]);

            // Delete old avatar if exists
            if ($oldAvatarUrl) {
                try {
                    // Try to extract path from URL
                    $oldPath = $this->extractStoragePath($oldAvatarUrl, $userId);
                    if ($oldPath && $oldPath !== $path) {
                        $disk->delete($oldPath);
                        // Also try s3 disk if we used public but old was s3
                        if ($disk->getConfig()['driver'] !== 's3') {
                            try { Storage::disk('s3')->delete($oldPath); } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Throwable $e) {
                    // don't fail upload if delete fails
                }
            }

            // Audit
            try {
                $this->auditLogService->log('avatar_uploaded', 'organizer', $organizer->id, [
                    'avatarUrl' => $avatarUrl,
                    'oldAvatarUrl' => $oldAvatarUrl,
                ], $user->id);
            } catch (\Throwable $e) {}

            return response()->json(['avatarUrl' => $avatarUrl], 200);
        } catch (\Throwable $e) {
            \Log::error('avatar upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload avatar'], 500);
        }
    }

    private function resizeImageTo400(string $content, string $mime): string
    {
        // Try GD resize to 400x400, fallback to original if GD not available or fails
        try {
            if (!extension_loaded('gd')) {
                return $content;
            }
            $src = @imagecreatefromstring($content);
            if (!$src) {
                return $content;
            }
            $width = imagesx($src);
            $height = imagesy($src);
            // Create 400x400 canvas, crop center
            $dst = imagecreatetruecolor(400, 400);
            // Preserve transparency for png/webp
            if (in_array($mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefill($dst, 0, 0, $transparent);
            }
            // Calculate crop
            $size = min($width, $height);
            $srcX = (int) (($width - $size) / 2);
            $srcY = (int) (($height - $size) / 2);
            imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, 400, 400, $size, $size);
            ob_start();
            if ($mime === 'image/png') {
                imagepng($dst);
            } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                imagewebp($dst);
            } else {
                imagejpeg($dst, null, 90);
            }
            $out = ob_get_clean();
            imagedestroy($src);
            imagedestroy($dst);
            return $out ?: $content;
        } catch (\Throwable $e) {
            return $content;
        }
    }

    private function extractStoragePath(?string $url, string $userId): ?string
    {
        if (!$url) return null;
        // If url is already a path like avatars/{userId}/...
        if (str_starts_with($url, 'avatars/')) {
            return $url;
        }
        // Try to parse URL and extract path
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) return null;
        // Remove leading /storage/ or bucket prefix
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        // Find avatars/ segment
        $pos = strpos($path, 'avatars/');
        if ($pos !== false) {
            return substr($path, $pos);
        }
        return null;
    }

    public function auditLog()
    {
        $organizer = Organizer::where('user_id', auth()->id())->orWhere('userId', auth()->id())->firstOrFail();
        return response()->json(['data' => []]);
    }
}
