<?php

namespace App\Features\EmailNotifications\Services;

use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Services\MjmlRenderer;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function __construct(private MjmlRenderer $mjmlRenderer)
    {
    }

    /**
     * Creates or updates a template. If $mjmlSource is given and MJML
     * rendering is enabled, compiles it to HTML and stores both; otherwise
     * treats $bodyOrMjml as raw HTML directly.
     */
    public function save(array $data, ?EmailTemplate $template = null): EmailTemplate
    {
        $mjmlSource = $data['mjml_source'] ?? null;
        $body = $data['body'] ?? '';

        if ($mjmlSource && $this->mjmlRenderer->isEnabled()) {
            $result = $this->mjmlRenderer->render($mjmlSource);

            if ($result['html']) {
                $body = $result['html'];
            }
            // If rendering failed, $result['errors'] is available to surface
            // to the admin UI - falls back to keeping whatever $body was
            // passed in (e.g. a previous successful compile) rather than
            // overwriting a working template with nothing.
        }

        $attributes = [
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $body,
            'mjml_source' => $mjmlSource,
        ];

        return $template
            ? tap($template)->update($attributes)
            : EmailTemplate::create($attributes);
    }

    /**
     * Sends a test email synchronously (not queued) for immediate admin
     * feedback, per Step 45's requirement.
     */
    public function sendTest(EmailTemplate $template, string $toEmail): bool
    {
        try {
            Mail::html($template->body, function ($message) use ($template, $toEmail) {
                $message->to($toEmail)->subject('[TEST] ' . $template->subject);
            });

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('EmailTemplateService::sendTest failed: ' . $e->getMessage());

            return false;
        }
    }
}
