<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\PushNotifications\Models\DeliveryPreferences;
use App\Features\PushNotifications\Models\PushNotificationTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    public function __construct(private \App\Features\PushNotifications\Services\PushNotificationService $pushNotificationService)
    {
    }

    public function preferences()
    {
        $preferences = DeliveryPreferences::firstOrCreate(
            ['user_id' => request()->user()->id]
        );

        // ->json(new JsonResource(...)) serializes WITHOUT the `data`
        // wrapper; the SPA api client reads response.data.data.* and the
        // endpoint contract tests assert the wrapped shape, so force the
        // standard resource envelope explicitly.
        return response()->json(
            \App\Features\PushNotifications\Http\Resources\DeliveryPreferencesResource::make($preferences)
                ->response()->getData(true)
        );
    }

    public function updatePreferences(Request $request)
    {
        $preferences = DeliveryPreferences::firstOrCreate(
            ['user_id' => $request->user()->id]
        );

        $validated = $request->validate([
            'push_notifications_enabled' => ['boolean'],
            'push_order_confirmation' => ['boolean'],
            'push_event_reminder' => ['boolean'],
            'push_checkin_alert' => ['boolean'],
            'push_promotional_offers' => ['boolean'],
        ]);

        $preferences->update($validated);

        // Same envelope as preferences() - see comment there.
        return response()->json(
            \App\Features\PushNotifications\Http\Resources\DeliveryPreferencesResource::make($preferences)
                ->response()->getData(true)
        );
    }

    public function templates()
    {
        $templates = PushNotificationTemplate::active()->get();

        return response()->json(
            \App\Features\PushNotifications\Http\Resources\PushNotificationTemplateResource::collection($templates)
        );
    }

    public function test(Request $request)
    {
        $validated = $request->validate([
            'template_id' => ['required', 'string', 'exists:push_notification_templates,id'],
            'variables' => ['required', 'array'],
        ]);

        $template = PushNotificationTemplate::findOrFail($validated['template_id']);

        if (!$template->is_active) {
            return response()->json(['message' => 'Template is not active'], 422);
        }

        $rendered = $template->render($validated['variables']);

        try {
            $this->pushNotificationService->sendToUser(
                $request->user()->id,
                $template->title,
                $template->body,
                $validated['variables']
            );

            return response()->json([
                'message' => 'Test notification sent successfully',
                'rendered' => $rendered,
            ]);
        } catch (\Throwable $e) {
            Log::error('Push notification test failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
                'rendered' => $rendered,
            ], 422);
        }
    }
}
