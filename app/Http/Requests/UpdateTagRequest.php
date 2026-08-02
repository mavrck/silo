<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tag'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags')
                    ->where('user_id', $this->user()->id)
                    ->ignore($tag->id),
            ],
        ];
    }
}
