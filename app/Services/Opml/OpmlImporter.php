<?php

namespace App\Services\Opml;

use App\Jobs\RefreshFeed;
use App\Models\Category;
use App\Models\User;
use SimpleXMLElement;

class OpmlImporter
{
    /**
     * Import an OPML document for the given user.
     *
     * @return int number of new feeds created
     */
    public function import(User $user, string $xml): int
    {
        $document = new SimpleXMLElement($xml);
        $created = 0;

        $defaultCategory = null;

        foreach ($document->body->outline as $outline) {
            if (isset($outline['xmlUrl'])) {
                $defaultCategory ??= $this->findOrCreateCategory($user, 'Imported');
                $created += $this->importFeed($user, $defaultCategory, $outline) ? 1 : 0;

                continue;
            }

            $category = $this->findOrCreateCategory($user, (string) ($outline['title'] ?? $outline['text'] ?? 'Imported'));

            foreach ($outline->outline as $feedOutline) {
                if (isset($feedOutline['xmlUrl'])) {
                    $created += $this->importFeed($user, $category, $feedOutline) ? 1 : 0;
                }
            }
        }

        return $created;
    }

    protected function findOrCreateCategory(User $user, string $name): Category
    {
        return $user->categories()->firstOrCreate(['name' => $name]);
    }

    protected function importFeed(User $user, Category $category, SimpleXMLElement $outline): bool
    {
        $url = (string) $outline['xmlUrl'];

        $feed = $user->feeds()->where('url', $url)->first();

        if ($feed) {
            return false;
        }

        $feed = $user->feeds()->create([
            'category_id' => $category->id,
            'title' => (string) ($outline['title'] ?? $outline['text'] ?? $url),
            'url' => $url,
            'site_url' => isset($outline['htmlUrl']) ? (string) $outline['htmlUrl'] : null,
        ]);

        RefreshFeed::dispatch($feed);

        return true;
    }
}
