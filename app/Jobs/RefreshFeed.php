<?php

namespace App\Jobs;

use App\Models\Feed;
use App\Services\Feeds\ContentSanitizer;
use App\Services\Feeds\PodcastEpisodeParser;
use FeedIo\Adapter\NotFoundException;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RefreshFeed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Feed $feed) {}

    public function handle(FeedIo $feedIo, ContentSanitizer $sanitizer, PodcastEpisodeParser $podcastParser): void
    {
        try {
            $result = $feedIo->read($this->feed->url, null, $this->feed->last_modified_at);
        } catch (Throwable $e) {
            $this->feed->forceFill([
                'last_fetched_at' => now(),
                'last_fetch_error' => $e instanceof NotFoundException
                    ? 'Feed not found (404).'
                    : $e->getMessage(),
            ])->save();

            return;
        }

        $response = $result->getResponse();

        if (! $response->isModified()) {
            $this->feed->forceFill([
                'last_fetched_at' => now(),
                'last_fetch_error' => null,
            ])->save();

            return;
        }

        $feedData = $result->getFeed();

        foreach ($feedData as $item) {
            $this->storeItem($item, $feedData, $sanitizer, $podcastParser);
        }

        $this->feed->forceFill([
            'last_fetched_at' => now(),
            'last_fetch_error' => null,
            'last_modified_at' => $response->getLastModified(),
            'title' => $this->feed->title ?: ($feedData->getTitle() ?? $this->feed->title),
            'site_url' => $this->feed->site_url ?: $feedData->getLink(),
            'description' => $this->feed->description ?: $feedData->getDescription(),
        ])->save();
    }

    protected function storeItem(
        ItemInterface $item,
        FeedInterface $feedData,
        ContentSanitizer $sanitizer,
        PodcastEpisodeParser $podcastParser): void
    {
        $guid = $item->getPublicId() ?: sha1(($item->getLink() ?? '').'|'.($item->getTitle() ?? ''));

        $entry = $this->feed->entries()->updateOrCreate(
            ['guid' => $guid],
            [
                'url' => $item->getLink(),
                'title' => $item->getTitle(),
                'author' => $item->getAuthor()?->getName(),
                'content' => $sanitizer->sanitize($item->getContent() ?? $item->getSummary()),
                'published_at' => $item->getLastModified(),
                ...$podcastParser->extract($item, $feedData),
            ]
        );

        if ($entry->wasRecentlyCreated) {
            if ($this->feed->summarize) {
                SummarizeEntry::dispatch($entry);
            }

            if ($this->feed->translate_to) {
                TranslateEntry::dispatch($entry);
            }
        }
    }
}
