<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\PushNotifications\Http\Resources\PushNotificationTemplateResource;
use App\Features\PushNotifications\Jobs\SendPushNotificationJob;
use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\PushNotifications\Models\PushNotificationTemplate;
use App\Features\PushNotifications\Requests\StorePushTemplateRequest;
use App\Features\PushNotifications\Requests\UpdatePushTemplateRequest;
use App\Features\PushNotifications\Services\PushNotificationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PushTemplateManagementController extends Controller
{
    public function __construct(private PushNotificationService $pushNotificationService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = PushNotificationTemplate::latest()->paginate(20);

        return PushNotificationTemplateResource::collection($templates);
    }

    public function store(StorePushTemplateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $template = PushNotificationTemplate::create($validated);

        return response()->json([
            'success' => true,
            'data' => new PushNotificationTemplateResource($template),
        ], 201);
    }

    public function update(UpdatePushTemplateRequest $request, string $templateId): JsonResponse
    {
        $template = PushNotificationTemplate::findOrFail($templateId);

        $validated = $request->validated();

        $template->update($validated);

        return response()->json([
            'success' => true,
            'data' => new PushNotificationTemplateResource($template),
        ]);
    }

    public function destroy(string $templateId)
    {
        $template = PushNotificationTemplate::findOrFail($templateId);

        $template->delete();

        return response()->noContent();
    }

    public function sendTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
        ]);

        $devices = PushNotificationDevice::where('user_id', $validated['user_id'])
            ->whereNull('deleted_at')
            ->get();

        $title = $validated['title'] ?? 'Test Notification';
        $body = $validated['body'] ?? 'This is a test notification.';

        foreach ($devices as $device) {
            SendPushNotificationJob::dispatch(
                $validated['user_id'],
                $title,
                $body,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Test notifications queued for ' . $devices->count() . ' device(s).',
        ], 202);
    }
}
