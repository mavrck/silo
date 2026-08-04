<?php

namespace App\Jobs;

use App\Agents\TranslateAgent;
use App\Models\Entry;
use App\Services\Feeds\ContentSanitizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class TranslateEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cap how much content is sent to the model per entry.
     */
    private const MAX_LENGTH = 20000;

    public function __construct(public readonly Entry $entry) {}

    public function handle(ContentSanitizer $sanitizer): void
    {
        $languageCode = $this->entry->feed->translate_to;

        if (! $languageCode || ! config('translation.enabled')) {
            return;
        }

        $languageName = config("translation.languages.{$languageCode}");
        $content = Str::limit(trim((string) $this->entry->content), self::MAX_LENGTH, '');

        try {
            $translatedTitle = $this->entry->title
                ? trim(TranslateAgent::make($languageName)->prompt($this->entry->title)->text)
                : $this->entry->title;

            $translatedContent = $sanitizer->sanitize(
                trim(TranslateAgent::make($languageName)->prompt($content)->text)
            );
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $this->entry->setTranslation($translatedTitle, $translatedContent, $languageCode);
    }
}
