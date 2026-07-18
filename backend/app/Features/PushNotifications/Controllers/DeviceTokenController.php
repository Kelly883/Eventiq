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
        $device = $this->pushNotificationService->registerDevice(
            $request->user()->id,
            $request->validated('fcm_token'),
            $request->validated('platform'),
            $request->validated('previous_token')
        );

        return response()->json(['id' => $device->id], 201);
    }

    public function destroy(Request $request, string $token)
    {
        $this->pushNotificationService->unregisterDevice($token);

        return response()->noContent();
    }
}
