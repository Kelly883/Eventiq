<?php

namespace App\Features\EmailNotifications\Services;

use App\Mail\TestEmailMailable;
use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Services\MjmlRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function __construct(private MjmlRenderer $mjmlRenderer)
    {
    }

    /**
     * Creates or updates a template. When mjml_body is present and MJML
     * rendering is enabled, compiles it to html_body via the MJML library.
     * If MJML is disabled or rendering fails, the supplied html_body (or the
     * existing compiled body) is retained and any compile errors are returned
     * so the controller can surface them to the admin UI.
     *
     * @param  array  $data
     * @param  EmailTemplate|null  $template
     * @return array{template: EmailTemplate, warnings: array<int,string>, errors: array<int,string>}
     */
    public function save(array $data, ?EmailTemplate $template = null): array
    {
        $mjml = $data['mjml_body'] ?? null;
        $html = $data['html_body'] ?? null;

        $errors = [];
        $warnings = [];

        if ($mjml !== null && $mjml !== '') {
            if ($this->mjmlRenderer->isEnabled()) {
                $result = $this->mjmlRenderer->render($mjml);

                if (! empty($result['html'])) {
                    $html = $result['html'];
                } elseif (! empty($result['errors'])) {
                    $errors = $result['errors'];
                    // Keep whatever html was supplied / already stored rather
                    // than overwriting a working template with nothing.
                }
            } else {
                $warnings[] = 'MJML rendering is disabled (MJML_ENABLED=false). mjml_body was stored without compilation.';
            }
        }

        $attributes = [
            'name' => $data['name'],
            'type' => $data['type'],
            'subject' => $data['subject'],
            'html_body' => $html ?? $template?->html_body ?? '',
            'mjml_body' => $mjml,
            'variables' => $data['variables'] ?? [],
            'is_active' => $data['is_active'] ?? ($template ? $template->is_active : true),
        ];

        $template = $template
            ? tap($template)->update($attributes)
            : EmailTemplate::create($attributes);

        return [
            'template' => $template,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * Sends a test email synchronously (not queued) for immediate admin
     * feedback. Returns false if the mail could not be sent.
     */
    public function sendTest(EmailTemplate $template, string $toEmail): bool
    {
        try {
            Mail::to($toEmail)->send(new TestEmailMailable(
                htmlContent: $template->html_body,
                recipientEmail: $toEmail,
                subject: $template->subject,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::error('EmailTemplateService::sendTest failed: ' . $e->getMessage());

            return false;
        }
    }
}

