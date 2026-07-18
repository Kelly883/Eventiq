# Logging and audit readiness

## Backend logging SDK decision

EventIQ's backend is Laravel/PHP, so Laravel's built-in Monolog integration is the correct logging SDK. Winston and Pino are Node.js loggers and are not required for the PHP request path. The only backend Node package in this repo is the MJML renderer helper, which is not responsible for application or audit logging.

## Production log destinations

Use `LOG_CHANNEL=stack` and set `LOG_STACK=daily,audit,syslog` in production when syslog or a platform log drain is configured. If your host already collects `storage/logs/*.log`, `LOG_STACK=daily,audit` is sufficient and the collector should forward both `laravel-*.log` and `audit-*.log` to the centralized service.

Set `AUDIT_EXTERNAL_LOG_SERVICE` to the actual destination name, for example `cloudwatch`, `datadog`, `splunk`, or `logtail`. The default `none` means the app is only writing local rotated files and the `audit_logs` database table.

## Retention

`LOG_DAILY_DAYS` controls rotated file count. `AUDIT_LOG_RETENTION_DAYS` controls database audit-row retention and is enforced by the scheduled `audit:prune` command.

## Production checklist

- Ensure the deploy user can write to `storage/logs`.
- Run `php artisan storage:link` if your deployment requires it for other storage paths.
- Run `php artisan audit:prune --days=365` manually once to verify retention permissions.
- Confirm the centralized log service receives records with `request_id`.

## Verification commands

Run `php artisan audit:verify-logging --write-test` during deployment to confirm `storage/logs` is writable and that the audit channel can create a test log record. The command prints the effective `LOG_CHANNEL`, `LOG_STACK`, and `AUDIT_EXTERNAL_LOG_SERVICE` values so release checks can catch missing centralized shipping configuration before audit generation starts.
