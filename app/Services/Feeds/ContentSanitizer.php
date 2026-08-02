<?php

namespace App\Services\Feeds;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class ContentSanitizer
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowLinkHosts(null)
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowMediaHosts(null)
            ->allowRelativeMedias();

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return $this->sanitizer->sanitize($html);
    }
}
