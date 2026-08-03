<?php

namespace App\Mail;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<string, Collection<string, Collection<int, Entry>>>  $groupedEntries  Entries grouped by category name, then feed title.
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $groupedEntries,
        public readonly string $frequency,
        public readonly int $totalCount,
        public readonly int $displayedCount,
    ) {}

    public function envelope(): Envelope
    {
        $noun = Str::plural('entry', $this->totalCount);

        return new Envelope(
            subject: "Your {$this->frequency} digest: {$this->totalCount} unread {$noun}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.digest',
            with: [
                'user' => $this->user,
                'groupedEntries' => $this->groupedEntries,
                'frequency' => $this->frequency,
                'totalCount' => $this->totalCount,
                'displayedCount' => $this->displayedCount,
            ],
        );
    }
}
