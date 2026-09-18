<?php

namespace App\Features\EmailNotifications\Controllers;

use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Features\EmailNotifications\Requests\StoreEmailTemplateRequest;
use App\Features\EmailNotifications\Requests\UpdateEmailTemplateRequest;
use App\Features\EmailNotifications\Resources\EmailTemplateResource;
use App\Features\EmailNotifications\Services\EmailTemplateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function __construct(private EmailTemplateService $templateService)
    {
    }

    /**
     * Ensure the authenticated user is an admin.
     * FIX: Use request-based auth check instead of $this->authorize()
     * BearerTokenAuth only sets request user resolver, not guard user
     */
    private function requireAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('admin') && !$user->hasRole('super-admin'))) {
            abort(403, 'Only admins can manage email templates.');
        }
    }

    public function index(Request $request)
    {
        $this->requireAdmin($request);

        return EmailTemplateResource::collection(EmailTemplate::latest()->get());
    }

    public function store(StoreEmailTemplateRequest $request)
    {
        $this->requireAdmin($request);

        $template = $this->templateService->save($request->validated());

        return new EmailTemplateResource($template);
    }

    public function show(Request $request, $emailTemplate)
    {
        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $this->requireAdmin($request);

        return new EmailTemplateResource($template);
    }

    public function update(UpdateEmailTemplateRequest $request, $emailTemplate)
    {
        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $this->requireAdmin($request);

        $template = $this->templateService->save($request->validated(), $template);

        return new EmailTemplateResource($template);
    }

    public function destroy(Request $request, $emailTemplate)
    {
        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $this->requireAdmin($request);

        $template->delete();

        return response()->noContent();
    }

    /**
     * POST /api/email-templates/{emailTemplate}/send-test - sends
     * synchronously (not queued) so the admin gets immediate feedback.
     */
    public function sendTest(Request $request, $emailTemplate)
    {
        $template = EmailTemplate::withTrashed()->findOrFail($emailTemplate);
        $this->requireAdmin($request);

        $validated = $request->validate(['email' => ['required', 'email']]);

        $sent = $this->templateService->sendTest($template, $validated['email']);

        return response()->json(['sent' => $sent], $sent ? 200 : 422);
    }

    /**
     * POST /api/email-templates/seed - creates default email templates
     */
    public function seed(Request $request)
    {
        $this->requireAdmin($request);

        $created = $this->templateService->seed();

        return EmailTemplateResource::collection($created);
    }
}
