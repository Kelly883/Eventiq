<?php

namespace App\Features\EmailNotifications\Controllers;

use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Features\EmailNotifications\Requests\StoreEmailTemplateRequest;
use App\Features\EmailNotifications\Requests\UpdateEmailTemplateRequest;
use App\Features\EmailNotifications\Resources\EmailTemplateResource;
use App\Features\EmailNotifications\Services\EmailTemplateService;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function __construct(private EmailTemplateService $templateService)
    {
    }

    private function requireAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('super-admin'))) {
            abort(403, 'Only admins can manage email templates.');
        }
    }

    public function index(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        $request->validate([
            'type' => ['nullable', 'string', 'in:order_confirmation,event_reminder,ticket_delivery,check_in_confirmation,refund_notification'],
            'is_active' => ['nullable', 'string', 'in:true,false'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = EmailTemplate::latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => EmailTemplateResource::collection($templates),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'from' => $templates->firstItem(),
                'last_page' => $templates->lastPage(),
                'to' => $templates->lastItem(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $this->requireAdmin($request);

        $result = $this->templateService->save($request->validated());
        $template = $result['template'];

        AuditLogger::log('create', $template, $request->user(), [
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => new EmailTemplateResource($template),
            'warnings' => $result['warnings'],
            'errors' => $result['errors'],
        ], 201);
    }

    public function show(Request $request, $emailTemplate): JsonResponse
    {
        $this->requireAdmin($request);

        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);

        return response()->json(['data' => new EmailTemplateResource($template)]);
    }

    public function update(UpdateEmailTemplateRequest $request, $emailTemplate): JsonResponse
    {
        $this->requireAdmin($request);

        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $original = $template->getOriginal();
        $changes = array_keys($request->validated());

        $result = $this->templateService->save($request->validated(), $template);
        $updated = $result['template'];

        $changedFields = [];
        foreach ($changes as $field) {
            if (($original[$field] ?? null) !== ($updated->getOriginal()[$field] ?? null)) {
                $changedFields[] = $field;
            }
        }
        if (! empty($changedFields)) {
            $changedFields[] = 'compiled_html_body';
        }

        AuditLogger::log('update', $updated, $request->user(), [
            'changed_fields' => $changedFields,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'data' => new EmailTemplateResource($updated),
            'warnings' => $result['warnings'],
            'errors' => $result['errors'],
        ]);
    }

    public function destroy(Request $request, $emailTemplate): JsonResponse
    {
        $this->requireAdmin($request);

        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $template->delete();

        AuditLogger::log('delete', $template, $request->user(), [
            'ip_address' => $request->ip(),
        ]);

        return response()->json(null, 204);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $this->requireAdmin($request);

        $validated = $request->validate([
            'templateId' => ['required', 'string', 'exists:email_templates,id'],
            'recipientEmail' => ['required', 'string', 'email:strict', 'max:255'],
        ]);

        $template = EmailTemplate::withTrashed()->findOrFail($validated['templateId']);

        $sent = $this->templateService->sendTest($template, $validated['recipientEmail']);

        if (! $sent) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email. Check mail configuration and logs.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test email sent',
        ]);
    }

    public function seed(Request $request)
    {
        $this->requireAdmin($request);

        return EmailTemplateResource::collection($this->templateService->seed());
    }
}

