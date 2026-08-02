<?php

namespace App\Models;

use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'read_at' => 'datetime',
            'is_read' => 'boolean',
            'is_starred' => 'boolean',
        ];
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
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
}
