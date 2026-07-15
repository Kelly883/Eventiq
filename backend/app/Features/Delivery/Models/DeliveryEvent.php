<?php

namespace App\Features\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryEvent extends Model
{
    use HasFactory;

    // Needed for the mass-assignment in TicketDeliveryService::sendViaDashboard().
    // Matches the columns that service expects delivery_events to eventually
    // have (user_id, ticket_reference, channel, payload) - not yet present
    // in the migration as of this commit; see that migration's TODO.
    protected $fillable = ['user_id', 'ticket_reference', 'channel', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];
}
