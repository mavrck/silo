<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'tags',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'entries_count' => $this->whenCounted('entries'),
            ],
        ];
    }
}
