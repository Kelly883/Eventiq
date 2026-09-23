<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\PushNotifications\Requests\DeleteDeviceTokenRequest;
use App\Features\PushNotifications\Requests\StoreDeviceTokenRequest;
use App\Features\PushNotifications\Services\PushNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenManagementController extends Controller
{
    public function __construct(private PushNotificationService $pushNotificationService)
    {
    }

    public function register(StoreDeviceTokenRequest $request): JsonResponse
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

        return response()->json(['success' => true, 'data' => $device], 201);
    }

    public function destroy(DeleteDeviceTokenRequest $request)
    {
        $validated = $request->validated();

        $device = PushNotificationDevice::where('token', $validated['token'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }

        $device->delete();

        return response()->noContent();
    }
}
