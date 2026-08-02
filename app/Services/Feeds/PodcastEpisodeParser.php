<?php

namespace App\Services\Feeds;

use FeedIo\Feed\ElementsAwareInterface;
use FeedIo\Feed\Item\MediaInterface;
use FeedIo\Feed\ItemInterface;
use FeedIo\Feed\NodeInterface;
use FeedIo\FeedInterface;

class PodcastEpisodeParser
{
    /**
     * Extract podcast episode fields from a feed item, if it carries an
     * audio or video enclosure. Returns an empty array for plain articles.
     *
     * @return array<string, mixed>
     */
    public function extract(ItemInterface $item, FeedInterface $feed): array
    {
        $enclosure = $this->findEnclosure($item);

        if ($enclosure === null) {
            return [];
        }

        return [
            'enclosure_url' => $enclosure->getUrl(),
            'enclosure_type' => $enclosure->getType(),
            'enclosure_length' => $this->toNullableInt($enclosure->getLength()),
            'duration_seconds' => $this->parseDuration($item->getValue('itunes:duration')),
            'episode_number' => $this->toNullableInt($item->getValue('itunes:episode')),
            'season_number' => $this->toNullableInt($item->getValue('itunes:season')),
            'image_url' => $this->findImageUrl($item) ?? $this->findImageUrl($feed),
        ];
    }

    private function findEnclosure(ItemInterface $item): ?MediaInterface
    {
        foreach ($item->getMedias() as $media) {
            $type = (string) $media->getType();

            if (str_starts_with($type, 'audio/') || str_starts_with($type, 'video/')) {
                return $media;
            }
        }

        return null;
    }

    private function findImageUrl(NodeInterface $node): ?string
    {
        if (! $node instanceof ElementsAwareInterface) {
            return null;
        }

        foreach ($node->getElementIterator('itunes:image') as $element) {
            $href = $element->getAttribute('href');

            if (! empty($href)) {
                return $href;
            }
        }

        return null;
    }

    private function parseDuration(?string $raw): ?int
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        if (! preg_match('/^\d{1,2}(:\d{1,2}){1,2}$/', $raw)) {
            return null;
        }

        $seconds = 0;
        foreach (explode(':', $raw) as $part) {
            $seconds = $seconds * 60 + (int) $part;
        }

        return $seconds;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ctype_digit((string) $value) ? (int) $value : null;
    }
}
