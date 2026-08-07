<?php

namespace App\Models;

use Database\Factories\FeedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    /** @use HasFactory<FeedFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'url',
        'site_url',
        'description',
        'favicon_url',
        'summarize',
        'translate_to',
        'digest_frequency',
    ];

    protected function casts(): array
    {
        return [
            'last_modified_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'summarize' => 'boolean',
            'digest_last_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function recordDigestSent(): void
    {
        $this->forceFill(['digest_last_sent_at' => now()])->save();
    }
}
