<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\PushNotifications\Http\Resources\PushNotificationTemplateResource;
use App\Features\PushNotifications\Models\PushNotificationTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminPushTemplateController extends Controller
{
    public function index()
    {
        $templates = PushNotificationTemplate::latest()->get();

        return PushNotificationTemplateResource::collection($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:10'],
            'badge' => ['nullable', 'integer', 'min:0'],
            'sound' => ['nullable', 'string', 'max:255'],
            'click_action' => ['nullable', 'string', 'max:255'],
            'collapse_key' => ['nullable', 'string', 'max:255'],
        ]);

        $template = PushNotificationTemplate::create($validated);

        return new PushNotificationTemplateResource($template);
    }

    public function show(PushNotificationTemplate $template)
    {
        return new PushNotificationTemplateResource($template);
    }

    public function update(Request $request, PushNotificationTemplate $template)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'max:100'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'variables' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'badge' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'sound' => ['sometimes', 'nullable', 'string', 'max:255'],
            'click_action' => ['sometimes', 'nullable', 'string', 'max:255'],
            'collapse_key' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $template->update($validated);

        return new PushNotificationTemplateResource($template);
    }

    public function destroy(PushNotificationTemplate $template)
    {
        $template->delete();

        return response()->noContent();
    }
}
