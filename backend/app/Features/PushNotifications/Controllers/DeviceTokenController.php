<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\OfflineSync\Services\OfflineSyncEngine;
use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\PushNotifications\Requests\StoreDeviceTokenRequest;
use App\Features\PushNotifications\Services\PushNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(private PushNotificationService $pushNotificationService)
    {
    }

    public function store(StoreDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        $device = $this->pushNotificationService->registerDevice(
            $request->user()->id,
            $validated['token'],
            $validated['provider'],
            $validated['device_type'],
            $validated['previous_token'] ?? null,
        );

        $device->update([
            'device_name' => $validated['device_name'] ?? null,
            'model' => $validated['model'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'os_version' => $validated['os_version'] ?? null,
            'locale' => $validated['locale'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
        ]);

        return response()->json(['id' => $device->id], 201);
    }

    public function destroy(Request $request, string $token)
    {
        $this->pushNotificationService->unregisterDevice($token);

        return response()->noContent();
    }

    public function updateOfflineStatus(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'offline_enabled' => ['required', 'boolean'],
        ]);

        $device = PushNotificationDevice::where('token_hash', hash('sha256', $token))
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Device not found'], 404);
        }

        $device->update([
            'offline_enabled' => $data['offline_enabled'],
        ]);
        $device->markAsUsed();

        return response()->json([
            'offline_enabled' => $device->offline_enabled,
        ]);
    }

    public function rotate(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentToken = $request->header('X-Device-Token');

        if ($currentToken) {
            $currentToken = strtolower($currentToken);
            PushNotificationDevice::where('token_hash', hash('sha256', $currentToken))
                ->where('user_id', $request->user()->id)
                ->delete();

            // The rotated token no longer exists — its queued offline
            // operations are orphaned.
            (new OfflineSyncEngine())->purgeDeviceOperations([$currentToken]);
        }

        return response()->json([
            'message' => 'Device token rotated. Generate a new client-side token.',
        ]);
    }
}
