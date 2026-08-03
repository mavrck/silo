<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'categories',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'position' => $this->position,
            ],
        ];
    }
}
