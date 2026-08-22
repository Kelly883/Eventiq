<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WebhookCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'perPage' => $this->perPage(),
                'currentPage' => $this->currentPage(),
                'totalPages' => $this->lastPage(),
                'hasMorePages' => $this->hasMorePages(),
            ],
        ];
    }

    public static $wrap = 'data';
}
