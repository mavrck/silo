<?php

namespace App\Mail;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FeedDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Entry>  $entries
     */
    public function __construct(
        public readonly User $user,
        public readonly Feed $feed,
        public readonly Collection $entries,
        public readonly string $frequency,
        public readonly int $totalCount,
        public readonly int $displayedCount,
    ) {}

    public function envelope(): Envelope
    {
        $noun = Str::plural('entry', $this->totalCount);

        return new Envelope(
            subject: "{$this->feed->title}: {$this->totalCount} unread {$noun}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feed-digest',
            with: [
                'user' => $this->user,
                'feed' => $this->feed,
                'entries' => $this->entries,
                'frequency' => $this->frequency,
                'totalCount' => $this->totalCount,
                'displayedCount' => $this->displayedCount,
            ],
        );
    }
}
