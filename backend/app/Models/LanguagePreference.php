<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

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
        'user_id' => 'required|string|exists:users,id',
        'language' => 'required|string|size:2',
        'date_format' => 'in:MM/DD/YYYY,DD/MM/YYYY,YYYY-MM-DD',
        'time_format' => 'in:12-hour,24-hour',
        'number_format' => 'in:comma,period',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $rules = static::$validationRules;
            $validator = Validator::make($model->attributesToArray(), $rules);
            if ($validator->fails()) {
                throw new \InvalidArgumentException($validator->errors()->first());
            }
        });
    }

    public static function getOrCreateForUser(string $userId): static
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'language' => 'en',
                'region' => 'US',
                'date_format' => 'MM/DD/YYYY',
                'time_format' => '12-hour',
                'currency' => 'USD',
                'number_format' => 'period',
                'rtl_enabled' => false,
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => (string) $this->user_id,
            'language' => $this->language,
            'region' => $this->region,
            'dateFormat' => $this->date_format,
            'timeFormat' => $this->time_format,
            'currency' => $this->currency,
            'numberFormat' => $this->number_format,
            'rtlEnabled' => (bool) $this->rtl_enabled,
            'createdAt' => $this->created_at?->toDateTimeString(),
            'updatedAt' => $this->updated_at?->toDateTimeString(),
        ];
    }

    public function detectRTL(): bool
    {
        return in_array($this->language, ['ar', 'he', 'ur'], true);
    }
}
