<?php

namespace App\Features\EmailNotifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'type' => $this->resource->type,
            'subject' => $this->resource->subject,
            'htmlBody' => $this->resource->html_body,
            'mjmlBody' => $this->resource->mjml_body,
            'variables' => $this->resource->variables ?? [],
            'isActive' => (bool) $this->resource->is_active,
            'publishedAt' => $this->resource->published_at,
            'version' => $this->resource->version,
            'category' => $this->resource->category,
            'description' => $this->resource->description,
            'previewHtml' => $this->resource->preview_html,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}
