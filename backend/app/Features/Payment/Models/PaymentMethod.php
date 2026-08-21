<?php

namespace App\Features\Payment\Models;

use App\Models\User;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });

        static::saving(function ($model) {
            if ($model->is_default) {
                PaymentMethod::where('user_id', $model->user_id)
                    ->where('gateway', $model->gateway)
                    ->where('id', '!=', $model->id)
                    ->whereNull('deleted_at')
                    ->update(['is_default' => false]);
            }
        });
    }

    protected $fillable = [
        'id',
        'user_id',
        'gateway',
        'type',
        'gateway_payment_method_id',
        'paystack_customer_code',
        'flutterwave_customer_id',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'bank_name',
        'account_name',
        'account_number_last4',
        'details',
        'is_default',
    ];

    protected $casts = [
        'gateway' => PaymentGateway::class,
        'type' => PaymentMethodType::class,
        'is_default' => 'boolean',
        'details' => 'array',
        'exp_month' => 'integer',
        'exp_year' => 'integer',
    ];

    protected $hidden = [
        'details',
        'paystack_customer_code',
        'flutterwave_customer_id',
        'last_four',
        'account_number_last4',
        'bank_name',
        'account_name',
    ];

    protected static array $validationRules = [
        'gateway' => 'required|in:paystack,flutterwave',
        'type' => 'required|in:card,bank_transfer,ussd,qr,mobile_money',
        'gateway_payment_method_id' => 'required|string|max:255',
        'paystack_customer_code' => 'nullable|string|max:255',
        'flutterwave_customer_id' => 'nullable|string|max:255',
        'brand' => 'nullable|string|max:50',
        'last_four' => 'nullable|string|size:4',
        'exp_month' => 'nullable|integer|min:1|max:12',
        'exp_year' => 'nullable|integer|min:2000|max:2100',
        'bank_name' => 'nullable|string|max:100',
        'account_name' => 'nullable|string|max:100',
        'account_number_last4' => 'nullable|string|size:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true)->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    public function isExpired(): bool
    {
        if (! $this->exp_month || ! $this->exp_year) {
            return false;
        }

        $now = now();
        $expiry = \Carbon\Carbon::create($this->exp_year, $this->exp_month, 1, 0, 0, 0)
            ->addMonth()
            ->endOfMonth();

        return $now->greaterThan($expiry);
    }

    public function getProviderCustomerReference(): ?string
    {
        return match ($this->gateway) {
            PaymentGateway::PAYSTACK => $this->paystack_customer_code,
            PaymentGateway::FLUTTERWAVE => $this->flutterwave_customer_id,
            default => null,
        };
    }

    public static function getValidationRules(): array
    {
        return static::$validationRules;
    }
}
