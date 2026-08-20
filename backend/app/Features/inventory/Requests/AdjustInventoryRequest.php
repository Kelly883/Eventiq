<?php

namespace App\Features\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'adjustment_type' => 'required|in:manual_increase,manual_decrease,reallocation,system_correction',
            'quantity_before' => 'required|integer|min:0',
            'quantity_after' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
        ];
    }

    /**
     * Application-level validation: ensure the ticket_tier_id (passed via route)
     * belongs to the event_id (also from route), preventing stale/cross-event references.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $eventId = $this->route('eventId');
            $ticketTierId = $this->route('ticketTierId');

            if ($eventId && $ticketTierId) {
                $exists = \App\Models\TicketTier::where('id', $ticketTierId)
                    ->where('event_id', $eventId)
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'ticket_tier_id',
                        'The specified ticket tier does not belong to the given event.'
                    );
                }
            }
        });
    }
}
