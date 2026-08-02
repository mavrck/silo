<?php

namespace App\Jobs;

use App\Models\Entry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Laravel\Ai\Agents\SummarizeAgent;
use Throwable;

class SummarizeEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Below this length of plain text, a summary isn't worth the API call.
     */
    private const MIN_LENGTH = 280;

    /**
     * Cap how much text is sent to the model per entry.
     */
    private const MAX_LENGTH = 8000;

    public function __construct(public readonly Entry $entry) {}

    public function handle(): void
    {
        $text = $this->plainTextContent();

        if (mb_strlen($text) < self::MIN_LENGTH) {
            return;
        }

        try {
            $response = SummarizeAgent::make(3)->prompt($text);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $this->entry->setSummary(trim($response->text));
    }

    private function plainTextContent(): string
    {
        $text = html_entity_decode(strip_tags((string) $this->entry->content), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return Str::limit(trim($text), self::MAX_LENGTH, '');
    }
}
