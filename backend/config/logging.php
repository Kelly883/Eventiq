<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'daily,audit')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => (int) env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        /*
        | Structured audit trail, separate from the application log and
        | from the audit_logs DB table (AuditLogService writes to both -
        | this file-based copy survives even if the database is
        | temporarily unavailable, per the original requirement). Not a
        | third-party aggregator (Datadog/Splunk/etc.) - nothing in this
        | codebase specifies one, and inventing a fake integration would
        | be worse than a real, working file-based trail with rotation.
        | Retention here is file-count based (LOG_DAILY_DAYS); the
        | separate AUDIT_LOG_RETENTION_DAYS setting governs how long DB
        | rows are kept, which is a different concern (compliance
        | retention vs disk space).
        */
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => (int) env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
