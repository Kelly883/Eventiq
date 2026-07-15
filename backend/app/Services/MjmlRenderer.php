<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Config;

/**
 * Compiles MJML markup to responsive HTML.
 *
 * MJML (https://mjml.io) is a Node.js package with no official PHP port -
 * it cannot be `composer require`'d or run natively inside Laravel. This
 * shells out to a small standalone Node script (backend/node-tools/mjml/
 * render.js) via Laravel's Process facade, which requires Node.js to be
 * installed on whatever server runs this - a real operational dependency
 * beyond the usual PHP stack. If MJML_ENABLED is false, or Node isn't
 * available, this fails gracefully rather than crashing the request.
 */
class MjmlRenderer
{
    public function isEnabled(): bool
    {
        return (bool) Config::get('mjml.enabled', false);
    }

    /**
     * @return array{html: ?string, errors: array<string>}
     */
    public function render(string $mjmlSource): array
    {
        if (! $this->isEnabled()) {
            return ['html' => null, 'errors' => ['MJML_ENABLED is false']];
        }

        $nodeBinary = Config::get('mjml.node_binary', 'node');
        $scriptPath = Config::get('mjml.render_script_path');

        if (! $scriptPath || ! is_file($scriptPath)) {
            Log::error('MjmlRenderer: render script not found', ['path' => $scriptPath]);

            return ['html' => null, 'errors' => ['MJML renderer script not found - has node-tools/mjml been npm installed?']];
        }

        try {
            $result = Process::timeout(15)
                ->input($mjmlSource)
                ->run([$nodeBinary, $scriptPath]);
        } catch (\Throwable $e) {
            // Most likely cause: Node.js isn't installed on this server at all.
            Log::error('MjmlRenderer: failed to invoke Node - is Node.js installed on this server? ' . $e->getMessage());

            return ['html' => null, 'errors' => ['MJML rendering unavailable - Node.js runtime not found on server']];
        }

        $decoded = json_decode($result->output(), true);

        if (! is_array($decoded)) {
            Log::error('MjmlRenderer: unexpected output from render script', ['output' => $result->output(), 'stderr' => $result->errorOutput()]);

            return ['html' => null, 'errors' => ['MJML renderer returned unexpected output']];
        }

        return ['html' => $decoded['html'] ?? null, 'errors' => $decoded['errors'] ?? []];
    }
}
