<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'entries',
            'id' => (string) $this->id,
            'attributes' => [
                'guid' => $this->guid,
                'url' => $this->url,
                'title' => $this->title,
                'author' => $this->author,
                'content' => $this->content,
                'summary' => $this->summary,
                'summarized_at' => $this->summarized_at,
                'published_at' => $this->published_at,
                'is_read' => $this->is_read,
                'read_at' => $this->read_at,
                'is_starred' => $this->is_starred,
                'enclosure_url' => $this->enclosure_url,
                'enclosure_type' => $this->enclosure_type,
                'enclosure_length' => $this->enclosure_length,
                'duration_seconds' => $this->duration_seconds,
                'episode_number' => $this->episode_number,
                'season_number' => $this->season_number,
                'image_url' => $this->image_url,
            ],
            'relationships' => [
                'feed' => [
                    'data' => ['type' => 'feeds', 'id' => (string) $this->feed_id],
                ],
                'tags' => [
                    'data' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                        'type' => 'tags',
                        'id' => (string) $tag->id,
                    ])->all()),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $included = [];

        if ($this->relationLoaded('feed') && $this->feed) {
            $included[] = (new FeedResource($this->feed))->resolve($request);
        }

        if ($this->relationLoaded('tags')) {
            foreach ($this->tags as $tag) {
                $included[] = (new TagResource($tag))->resolve($request);
            }
        }

        return $included ? ['included' => $included] : [];
    }
}
