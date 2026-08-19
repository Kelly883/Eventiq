<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'passwordHash',
        'role',
        'role_id',
        'permissions',
        'emailVerified',
        'status',
        'lastLoginAt',
        'paystack_customer_code',
        'flutterwave_customer_id',
        'default_payment_gateway',
        'default_payment_method_id',
        'trial_ends_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'passwordHash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emailVerified' => 'boolean',
            'lastLoginAt' => 'datetime',
            'trial_ends_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'userId');
    }

    public function passwordResetTokens(): HasMany
    {
        return $this->hasMany(PasswordResetToken::class, 'userId');
    }

    public function hasRole(string $roleName): bool
    {
        if ($this->relationLoaded('roleRelation') && $this->roleRelation !== null) {
            return $this->roleRelation->name === $roleName;
        }

        if ($this->roleRelation()->where('name', $roleName)->exists()) {
            return true;
        }

        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permissionName): bool
    {
        // Check direct permission or via role
        return $this->permissions()->where('name', $permissionName)->exists() ||
               $this->roles()->whereHas('permissions', function ($q) use ($permissionName) {
                   $q->where('name', $permissionName);
               })->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function organizer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Organizer::class);
    }

    public function paymentMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Features\Payment\Models\PaymentMethod::class);
    }

    public function getDefaultPaymentMethod(): ?\App\Features\Payment\Models\PaymentMethod
    {
        return $this->paymentMethods()->where('is_default', true)->whereNull('deleted_at')->first();
    }

    public function getDefaultPaymentMethodForGateway(string $gateway): ?\App\Features\Payment\Models\PaymentMethod
    {
        return $this->paymentMethods()
            ->where('gateway', $gateway)
            ->where('is_default', true)
            ->whereNull('deleted_at')
            ->first();
    }

    public function hasPaystackCustomer(): bool
    {
        return ! empty($this->paystack_customer_code);
    }

    public function hasFlutterwaveCustomer(): bool
    {
        return ! empty($this->flutterwave_customer_id);
    }

    public function getPaystackCustomerReference(): ?string
    {
        return $this->paystack_customer_code ?: null;
    }

    public function getFlutterwaveCustomerReference(): ?string
    {
        return $this->flutterwave_customer_id ?: null;
    }

    /**
     * Get the dashboard preferences for the user (one-to-one).
     */
    public function dashboardPreferences(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Features\Dashboard\Models\UserDashboardPreference::class, 'user_id', 'id');
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AuditLog::class);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'suspended' => 'red',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'suspended' => 'Suspended',
            'pending' => 'Pending',
            default => ucfirst($this->status),
        };
    }

    public function isPasswordCorrect(string $password): bool
    {
        return \Illuminate\Support\Facades\Hash::check($password, $this->passwordHash);
    }

    public function invalidateAllSessions(): int
    {
        return $this->sessions()->whereNull('revokedAt')->update(['revokedAt' => now()]);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret);
    }

    public function enableTwoFactor(string $secret, array $recoveryCodes): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ])->save();
    }

    public function disableTwoFactor(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function getTwoFactorRecoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes, true) ?: [];
    }

    public function replaceTwoFactorRecoveryCodes(array $recoveryCodes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ])->save();
    }
}