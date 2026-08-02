<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('saved_searches')->where('user_id', $this->user()->id),
            ],
            'q' => ['nullable', 'string', 'max:255'],
            'feed_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'unread' => ['nullable', 'boolean'],
            'starred' => ['nullable', 'boolean'],
        ];
    }
}
