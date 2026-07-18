<?php

return [
    'enabled' => env('AUDIT_LOG_ENABLED', true),

    'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Centralized log shipping
    |--------------------------------------------------------------------------
    |
    | Laravel already uses Monolog, so a Node-only Winston/Pino SDK is not
    | required for this PHP backend. Production deployments should ship the
    | `daily` and `audit` channels by including `syslog` in LOG_STACK or by
    | attaching a platform log drain/agent (CloudWatch, Datadog, Splunk,
    | Logtail, etc.) to storage/logs/*.log. Set AUDIT_EXTERNAL_LOG_SERVICE to
    | the real destination name for operational visibility; `none` means local
    | rotated files/database only.
    |
    */
    'external_log_service' => env('AUDIT_EXTERNAL_LOG_SERVICE', 'none'),
];
