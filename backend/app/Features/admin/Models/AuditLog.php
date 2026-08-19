<?php

namespace App\Features\admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (AuditLog $log) {
            if (empty($log->{$log->getKeyName()})) {
                $log->{$log->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $table = 'audit_logs';

    protected $fillable = [
        'id',
        'user_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'metadata',
        'status',
    ];

    protected $casts = [
        'description' => 'string',
        'metadata' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByAdmin($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failure' => 'red',
            'warning' => 'amber',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'success' => 'Success',
            'failure' => 'Failed',
            'warning' => 'Warning',
            default => ucfirst($this->status),
        };
    }
}
