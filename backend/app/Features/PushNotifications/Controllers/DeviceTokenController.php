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
}
