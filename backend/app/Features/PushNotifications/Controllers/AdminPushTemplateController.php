<?php

namespace App\Features\PushNotifications\Controllers;

use App\Features\PushNotifications\Models\PushNotificationTemplate;
use App\Features\PushNotifications\Requests\StorePushTemplateRequest;
use App\Features\PushNotifications\Requests\UpdatePushTemplateRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Features\PushNotifications\Resources\PushNotificationTemplateResource;

class AdminPushTemplateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $templates = PushNotificationTemplate::query()
            ->when(request('type'), fn ($q, $type) => $q->where('type', $type))
            ->when(request('is_active') !== null, fn ($q, $active) => $q->where('is_active', $active))
            ->orderBy('created_at', 'desc')
            ->paginate();

        return PushNotificationTemplateResource::collection($templates);
    }

    public function store(StorePushTemplateRequest $request): JsonResponse
    {
        $template = PushNotificationTemplate::create($request->validated());

        return (new PushNotificationTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PushNotificationTemplate $template): PushNotificationTemplateResource
    {
        return new PushNotificationTemplateResource($template);
    }

    public function update(UpdatePushTemplateRequest $request, PushNotificationTemplate $template): PushNotificationTemplateResource
    {
        $template->update($request->validated());

        return new PushNotificationTemplateResource($template);
    }

    public function destroy(PushNotificationTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }
}
