<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LanguagePreference extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    protected $table = 'localization_preferences';

    protected $fillable = [
        'user_id',
        'language',
        'region',
        'date_format',
        'time_format',
        'currency',
        'number_format',
        'rtl_enabled',
    ];

    protected $casts = [
        'rtl_enabled' => 'boolean',
    ];

    protected static array $validationRules = [
        'language' => 'string|size:2',
        'date_format' => 'in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD',
        'time_format' => 'in:12-hour,24-hour',
        'number_format' => 'in:comma,period',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateForUser(string $userId): static
    {
        $preference = static::where('user_id', $userId)->first();

        if ($preference) {
            return $preference;
        }

        return static::create([
            'user_id' => $userId,
            'language' => 'en',
            'region' => 'US',
            'date_format' => 'MM/DD/YYYY',
            'time_format' => '12-hour',
            'currency' => 'USD',
            'number_format' => 'period',
            'rtl_enabled' => false,
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'language' => $this->language,
            'region' => $this->region,
            'dateFormat' => $this->date_format,
            'timeFormat' => $this->time_format,
            'currency' => $this->currency,
            'numberFormat' => $this->number_format,
            'rtlEnabled' => (bool) $this->rtl_enabled,
        ];
    }

    public function detectRTL(): bool
    {
        return in_array($this->language, ['ar', 'he', 'ur'], true);
    }
}
