<?php

namespace App\Features\Payouts\Requests;

use App\Features\Payouts\Models\Payout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validates the query-string params for GET /api/organizer/payouts/list.
 *
 * Authorisation ordering is important and deliberately enforced here:
 *   bearer middleware  -> 401  (no / invalid token)
 *   this FormRequest::authorize  -> 403  (authenticated but not an organiser)
 *   this FormRequest::rules       -> 400  (syntactically invalid query params)
 *
 * `authorize()` runs before `validate()`, so a non-organiser receives a clean
 * 403 even if their query string is also malformed (403 takes precedence).
 */
class ListPayoutsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Organisers (users with an organiser profile) and admins may list payouts.
        // Admins see every organiser's payouts; organisers are scoped in the controller.
        return $user->organizer !== null
            || $user->hasRole('admin')
            || $user->hasRole('super-admin');
    }

    public function rules(): array
    {
        $validStatuses = implode(',', [
            Payout::STATUS_PENDING,
            Payout::STATUS_CALCULATED,
            Payout::STATUS_APPROVED,
            Payout::STATUS_PROCESSING,
            Payout::STATUS_COMPLETED,
            Payout::STATUS_FAILED,
        ]);

        return [
            // Filter: payout status (must be a known status).
            'status'     => ['nullable', 'string', "in:{$validStatuses}"],
            // Filter: created_at date window (ISO Y-m-d), inclusive, start <= end.
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            // Pagination: limit (alias of per_page) capped at 100 to prevent huge pulls.
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'       => ['nullable', 'integer', 'min:1'],
            // Sorting (validated to avoid arbitrary column injection).
            'sort_by'    => ['nullable', 'in:createdAt,payoutAmount'],
            'sort_dir'   => ['nullable', 'in:asc,desc'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.max'      => 'The limit may not be greater than 100.',
            'per_page.max'   => 'The per_page may not be greater than 100.',
            'sort_by.in'     => 'The sort_by must be one of: createdAt, payoutAmount.',
            'sort_dir.in'    => 'The sort_dir must be one of: asc, desc.',
            'status.in'      => 'The status must be one of: pending, calculated, approved, processing, completed, failed.',
            'start_date.date_format' => 'start_date must use the YYYY-MM-DD format.',
            'end_date.date_format'   => 'end_date must use the YYYY-MM-DD format.',
            'end_date.after_or_equal' => 'end_date must be on or after start_date.',
        ];
    }

    /**
     * Use a 400 (Bad Request) rather than Laravel's default 422 so the
     * contract matches the documented API behaviour for invalid params.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors(),
            ], 400)
        );
    }
}
