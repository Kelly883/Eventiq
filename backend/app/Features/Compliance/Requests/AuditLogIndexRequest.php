<?php

namespace App\Features\Compliance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:100'],
            'targetType' => ['nullable', 'string', 'max:100'],
            'targetId' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:success,failure,warning'],
            'classification' => ['nullable', 'string', 'max:100'],
            'userId' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'sortBy' => ['nullable', 'in:createdAt,action,status'],
            'sortOrder' => ['nullable', 'in:asc,desc'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'format' => ['nullable', 'in:csv,json'],
        ];
    }

    /**
     * If both `limit` and `per_page` are provided, `limit`
     * wins and is clamped to 100. Otherwise `per_page` is
     * used (default 20).
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            $data = array_merge([
                'action' => null,
                'targetType' => null,
                'targetId' => null,
                'status' => null,
                'classification' => null,
                'userId' => null,
                'from' => null,
                'to' => null,
                'sortBy' => 'createdAt',
                'sortOrder' => 'desc',
                'limit' => null,
                'per_page' => 20,
            ], $data);

            if ($data['limit'] !== null && (int) $data['limit'] > 0) {
                $data['per_page'] = min((int) $data['limit'], 100);
            }

            $data['per_page'] = min((int) $data['per_page'], 100);
        }

        return $data;
    }
}
