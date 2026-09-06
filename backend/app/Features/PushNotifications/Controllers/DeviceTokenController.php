<?php

namespace App\Features\PushNotifications\Controllers;

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
        \Log::info('updateOfflineStatus request', [
            'content' => $request->getContent(),
            'all' => $request->all(),
            'has_offline_enabled' => $request->has('offline_enabled'),
            'input_offline_enabled' => $request->input('offline_enabled'),
        ]);
        $data = $request->validate([
            'offline_enabled' => ['required', 'boolean'],
        ]);

        $device = $this->pushNotificationService->registerDevice(
            $request->user()->id,
            $token,
            $request->input('provider', 'web'),
            $request->input('device_type', 'web')
        );

        $device->update([
            'offline_enabled' => $data['offline_enabled'],
        ]);
        $device->markAsUsed();

        return response()->json([
            'token' => $device->token,
            'offline_enabled' => $device->offline_enabled,
        ]);
    }

    public function rotate(Request $request): \Illuminate\Http\JsonResponse
    {
        $currentToken = $request->header('X-Device-Token');

        if ($currentToken) {
            PushNotificationDevice::where('token', strtolower($currentToken))
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        return response()->json([
            'message' => 'Device token rotated. Generate a new client-side token.',
        ]);
    }
}
