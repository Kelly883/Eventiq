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

    public function index()
    {
        $this->authorize('viewAny', EmailTemplate::class);

        return EmailTemplateResource::collection(EmailTemplate::latest()->get());
    }

    public function store(StoreEmailTemplateRequest $request)
    {
        $template = $this->templateService->save($request->validated());

        return new EmailTemplateResource($template);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);

        return new EmailTemplateResource($emailTemplate);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate)
    {
        $template = $this->templateService->save($request->validated(), $emailTemplate);

        return new EmailTemplateResource($template);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $this->authorize('delete', $emailTemplate);
        $emailTemplate->delete();

        return response()->noContent();
    }

    /**
     * POST /api/email-templates/{emailTemplate}/send-test - sends
     * synchronously (not queued) so the admin gets immediate feedback.
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        $this->authorize('view', $emailTemplate);

        $validated = $request->validate(['email' => ['required', 'email']]);

        $sent = $this->templateService->sendTest($emailTemplate, $validated['email']);

        return response()->json(['sent' => $sent], $sent ? 200 : 422);
    }
}
