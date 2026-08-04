<?php

namespace App\Http\Requests;

use App\Models\Feed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Feed::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                Rule::unique('feeds')->where('user_id', $this->user()->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'summarize' => ['sometimes', 'boolean'],
            'translate_to' => config('translation.enabled')
                ? ['nullable', 'string', Rule::in(array_keys(config('translation.languages')))]
                : ['prohibited'],
        ];
    }
}
