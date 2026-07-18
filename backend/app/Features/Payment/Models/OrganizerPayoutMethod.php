<?php

namespace App\Features\Payment\Models;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizerPayoutMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id', 'bank_code', 'bank_name',
        'account_number', 'account_name', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Shape PaymentGatewayService::initiatePayout() expects for
     * $bankDetails.
     */
    public function toBankDetailsArray(): array
    {
        return [
            'account_number' => $this->account_number,
            'bank_code' => $this->bank_code,
            'account_name' => $this->account_name,
        ];
    }
}
