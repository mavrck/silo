<?php

namespace App\Models;

use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    protected $fillable = [
        'guid',
        'url',
        'title',
        'author',
        'content',
        'published_at',
        'enclosure_url',
        'enclosure_type',
        'enclosure_length',
        'duration_seconds',
        'episode_number',
        'season_number',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'read_at' => 'datetime',
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
            'summarized_at' => 'datetime',
            'translated_at' => 'datetime',
            'enclosure_length' => 'integer',
            'duration_seconds' => 'integer',
            'episode_number' => 'integer',
            'season_number' => 'integer',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereFullText(['title', 'content'], $term);
    }

    public function markRead(): void
    {
        if (! $this->is_read) {
            $this->forceFill(['is_read' => true, 'read_at' => now()])->save();
        }
    }

    public function markUnread(): void
    {
        $this->forceFill(['is_read' => false, 'read_at' => null])->save();
    }

    public function toggleStarred(): void
    {
        $this->forceFill(['is_starred' => ! $this->is_starred])->save();
    }

    public function setSummary(string $summary): void
    {
        $this->forceFill(['summary' => $summary, 'summarized_at' => now()])->save();
    }

    public function setTranslation(string $title, string $content, string $language): void
    {
        $this->forceFill([
            'translated_title' => $title,
            'translated_content' => $content,
            'translated_language' => $language,
            'translated_at' => now(),
        ])->save();
    }

    public function isPodcastEpisode(): bool
    {
        return ! is_null($this->enclosure_url);
    }
}
