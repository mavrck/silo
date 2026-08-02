<?php

namespace App\Services\Opml;

use App\Models\User;
use SimpleXMLElement;

class OpmlExporter
{
    public function export(User $user): string
    {
        $opml = new SimpleXMLElement('<opml version="2.0"></opml>');

        $head = $opml->addChild('head');
        $head->addChild('title', htmlspecialchars("{$user->name}'s feeds"));

        $body = $opml->addChild('body');

        $categories = $user->categories()->with('feeds')->orderBy('name')->get();

        foreach ($categories as $category) {
            if ($category->feeds->isEmpty()) {
                continue;
            }

            $categoryOutline = $body->addChild('outline');
            $categoryOutline->addAttribute('text', $category->name);
            $categoryOutline->addAttribute('title', $category->name);

            foreach ($category->feeds as $feed) {
                $feedOutline = $categoryOutline->addChild('outline');
                $feedOutline->addAttribute('type', 'rss');
                $feedOutline->addAttribute('text', $feed->title);
                $feedOutline->addAttribute('title', $feed->title);
                $feedOutline->addAttribute('xmlUrl', $feed->url);

                if ($feed->site_url) {
                    $feedOutline->addAttribute('htmlUrl', $feed->site_url);
                }
            }
        }

        return $opml->asXML();
    }
}
