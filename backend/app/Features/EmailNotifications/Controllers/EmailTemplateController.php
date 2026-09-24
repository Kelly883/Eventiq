<?php

namespace App\Features\EmailNotifications\Controllers;

use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Features\EmailNotifications\Requests\StoreEmailTemplateRequest;
use App\Features\EmailNotifications\Requests\UpdateEmailTemplateRequest;
use App\Features\EmailNotifications\Resources\EmailTemplateResource;
use App\Features\EmailNotifications\Services\EmailTemplateService;
use App\Http\Controllers\Controller;
use App\Mail\TestEmailMailable;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    public function __construct(private EmailTemplateService $templateService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'filter.type' => ['nullable', 'string', 'max:50'],
            'filter.is_active' => ['nullable', 'string', 'in:true,false'],
            'filter.search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = EmailTemplate::query()->orderByDesc('created_at');

        if ($request->filled('filter.type')) {
            $query->where('type', $request->input('filter.type'));
        }

        if ($request->filled('filter.is_active')) {
            $query->where('is_active', $request->boolean('filter.is_active'));
        }

        if ($request->filled('filter.search')) {
            $search = $request->input('filter.search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('type', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%");
            });
        }

        $templates = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => EmailTemplateResource::collection($templates),
            'meta' => [
                'total' => $templates->total(),
                'per_page' => $templates->perPage(),
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    public function show(string $templateId): JsonResponse
    {
        $template = EmailTemplate::query()->find($templateId);

        if (! $template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new EmailTemplateResource($template),
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['mjml_body'])) {
            $validated['html_body'] = $this->compileMjml($validated['mjml_body']);
        }

        unset($validated['mjml_body']);

        $template = EmailTemplate::query()->create($validated);

        return response()->json([
            'success' => true,
            'data' => new EmailTemplateResource($template),
        ], 201);
    }

    public function update(UpdateEmailTemplateRequest $request, string $templateId): JsonResponse
    {
        $template = EmailTemplate::query()->find($templateId);

        if (! $template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
            ], 404);
        }

        $validated = $request->validated();

        if (! empty($validated['mjml_body'])) {
            $validated['html_body'] = $this->compileMjml($validated['mjml_body']);
        }

        unset($validated['mjml_body']);

        $changedFields = [];

        foreach ($validated as $field => $value) {
            if ($template->{$field} !== $value) {
                $changedFields[$field] = [
                    'old' => $template->{$field},
                    'new' => $value,
                ];
            }
        }

        $template->update($validated);

        AuditLog::create([
            'action' => 'email_template_updated',
            'target_type' => 'email_template',
            'target_id' => $template->id,
            'user_id' => $request->user()?->id,
            'changed_fields' => $changedFields,
        ]);

        return response()->json([
            'success' => true,
            'data' => new EmailTemplateResource($template),
        ]);
    }

    public function destroy(string $templateId): JsonResponse
    {
        $template = EmailTemplate::query()->find($templateId);

        if (! $template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
            ], 404);
        }

        if ($template->is_system_template) {
            return response()->json([
                'success' => false,
                'message' => 'System templates cannot be deleted.',
            ], 403);
        }

        $template->delete();

        AuditLog::create([
            'action' => 'email_template_deleted',
            'target_type' => 'email_template',
            'target_id' => $template->id,
            'user_id' => auth()->id(),
            'changed_fields' => [
                'deleted_at' => now()->toDateTimeString(),
            ],
        ]);

        return response()->json(null, 204);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'integer'],
            'recipient_email' => ['required', 'string', 'email'],
        ]);

        $template = EmailTemplate::query()->find($validated['template_id']);

        if (! $template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found.',
            ], 404);
        }

        try {
            Mail::to($validated['recipient_email'])->queue(
                new TestEmailMailable(
                    htmlContent: $template->html_body,
                    recipientEmail: $validated['recipient_email'],
                    subject: $template->subject,
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email queued',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function compileMjml(string $mjml): string
    {
        try {
            $mjmlService = new \MjmlPHP\Mjml();
            $result = $mjmlService->toHtml($mjml);
            return $result->html();
        } catch (\Throwable $e) {
            Log::warning('MJML compilation failed: ' . $e->getMessage() . '. Using fallback.');
            return $this->compileMjmlFallback($mjml);
        }
    }

    private function compileMjmlFallback(string $mjml): string
    {
        // Basic MJML to HTML conversion fallback
        $html = $mjml;
        $html = preg_replace('/<mjml>/', '<html>', $html);
        $html = preg_replace('/<\/mjml>/', '</html>', $html);
        $html = preg_replace('/<mj-head>.*?<\/mj-head>/s', '', $html);
        $html = preg_replace('/<mj-body>/', '<body>', $html);
        $html = preg_replace('/<\/mj-body>/', '</body>', $html);
        $html = preg_replace('/<mj-section>/', '<div style="margin:0 auto;max-width:600px;">', $html);
        $html = preg_replace('/<\/mj-section>/', '</div>', $html);
        $html = preg_replace('/<mj-column>/', '<div>', $html);
        $html = preg_replace('/<\/mj-column>/', '</div>', $html);
        $html = preg_replace('/<mj-text>/', '<div>', $html);
        $html = preg_replace('/<\/mj-text>/', '</div>', $html);
        $html = preg_replace('/<mj-image[^>]*src="([^"]+)"[^>]*>/', '<img src="$1" style="max-width:100%;">', $html);
        $html = preg_replace('/<mj-button[^>]*href="([^"]+)"[^>]*>(.*?)<\/mj-button>/s', '<a href="$1" style="display:inline-block;padding:10px 20px;background:#6366f1;color:#fff;text-decoration:none;border-radius:5px;">$2</a>', $html);

        return $html;
    }
}
