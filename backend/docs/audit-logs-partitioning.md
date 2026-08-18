# Audit Logs Partitioning Guide

This document provides guidance for partitioning the `audit_logs` table when it grows beyond 10M rows.

## When to Partition

- Current size: < 1M rows — no partitioning needed, use composite indexes
- 1M - 10M rows — add covering index on `(action, created_at)`, consider quarterly partitions
- > 10M rows — implement monthly partitioning by `created_at`

## PostgreSQL Monthly Partitioning

```sql
-- 1. Create the partitioned table
CREATE TABLE audit_logs_partitioned (
    id uuid NOT NULL,
    user_id uuid NULL,
    ip_address varchar(45) NULL,
    user_agent varchar(255) NULL,
    action varchar(100) NOT NULL,
    target_type varchar(50) NULL,
    target_id varchar(255) NULL,
    description text NULL,
    metadata jsonb NULL,
    status varchar(50) NULL,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
) PARTITION BY RANGE (created_at);

-- 2. Create partitions for each month
CREATE TABLE audit_logs_2024_01 PARTITION OF audit_logs_partitioned
    FOR VALUES FROM ('2024-01-01') TO ('2024-02-01');

CREATE TABLE audit_logs_2024_02 PARTITION OF audit_logs_partitioned
    FOR VALUES FROM ('2024-02-01') TO ('2024-03-01');

-- Repeat for each month...

-- 3. Create indexes on each partition (or use the parent table)
CREATE INDEX idx_audit_logs_action_created_at ON audit_logs_partitioned (action, created_at);
CREATE INDEX idx_audit_logs_user_id_action_created_at ON audit_logs_partitioned (user_id, action, created_at);
CREATE INDEX idx_audit_logs_target_type_target_id_created_at ON audit_logs_partitioned (target_type, target_id, created_at);

-- 4. Migrate existing data
INSERT INTO audit_logs_partitioned SELECT * FROM audit_logs;

-- 5. Swap tables (in a maintenance window)
BEGIN;
    ALTER TABLE audit_logs RENAME TO audit_logs_old;
    ALTER TABLE audit_logs_partitioned RENAME TO audit_logs;
COMMIT;

-- 6. Drop old table after verification
-- DROP TABLE audit_logs_old;
```

## MySQL Monthly Partitioning

```sql
-- 1. Create partitioned table
CREATE TABLE audit_logs_partitioned (
    id varchar(36) NOT NULL,
    user_id varchar(36) NULL,
    ip_address varchar(45) NULL,
    user_agent varchar(255) NULL,
    action varchar(100) NOT NULL,
    target_type varchar(50) NULL,
    target_id varchar(255) NULL,
    description text NULL,
    metadata json NULL,
    status varchar(50) NULL,
    created_at datetime NULL,
    updated_at datetime NULL,
    deleted_at datetime NULL,
    PRIMARY KEY (id, created_at)
) ENGINE=InnoDB
PARTITION BY RANGE COLUMNS(created_at) (
    PARTITION p202401 VALUES LESS THAN ('2024-02-01'),
    PARTITION p202402 VALUES LESS THAN ('2024-03-01'),
    PARTITION p202403 VALUES LESS THAN ('2024-04-01')
    -- Add future partitions
);

-- 2. Add indexes
CREATE INDEX idx_audit_logs_action_created_at ON audit_logs_partitioned (action, created_at);
CREATE INDEX idx_audit_logs_user_id_action_created_at ON audit_logs_partitioned (user_id, action, created_at);
```

## Automatic Partition Management

For PostgreSQL, use a scheduled job or cron to create future partitions:

```php
// app/Console/Commands/CreateAuditLogPartition.php
public function handle()
{
    $nextMonth = now()->addMonth()->startOfMonth();
    $tableName = 'audit_logs_' . $nextMonth->format('Y_m');

    DB::statement("
        CREATE TABLE IF NOT EXISTS {$tableName} PARTITION OF audit_logs
        FOR VALUES FROM ('{$nextMonth->startOfMonth()->toDateString()}') 
        TO ('{$nextMonth->endOfMonth()->addDay()->toDateString()}')
    ");
}
```

## Indexes for Common Queries

| Query Pattern | Recommended Index |
|---------------|-------------------|
| `byAction('user_suspended')->recent(7)` | `(action, created_at)` |
| `byAdmin($userId)->recent(7)` | `(user_id, action, created_at)` |
| `byTargetType('order')->get()` | `(target_type, target_id, created_at)` |
| Global recent audit log | `(created_at)` with partitioning |

## Notes

- Always create the covering index before implementing partitioning
- Keep at least 3-6 months of partitions in the past for historical queries
- Set up automated partition cleanup for old data beyond retention period
- Monitor query plans to ensure indexes are being used
- Test partitioning in staging before applying to production
