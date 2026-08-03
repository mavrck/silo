<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'feeds',
            'id' => (string) $this->id,
            'attributes' => [
                'title' => $this->title,
                'url' => $this->url,
                'site_url' => $this->site_url,
                'description' => $this->description,
                'last_fetched_at' => $this->last_fetched_at,
                'last_fetch_error' => $this->last_fetch_error,
                'summarize' => $this->summarize,
            ],
            'relationships' => [
                'category' => [
                    'data' => ['type' => 'categories', 'id' => (string) $this->category_id],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        if (! $this->relationLoaded('category') || ! $this->category) {
            return [];
        }

        return [
            'included' => [
                (new CategoryResource($this->category))->resolve($request),
            ],
        ];
    }
}
