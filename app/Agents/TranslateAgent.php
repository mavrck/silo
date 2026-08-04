<?php

namespace App\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

final class TranslateAgent implements Agent
{
    use Promptable;

    public function __construct(private readonly string $language) {}

    public function instructions(): string
    {
        return "Translate the following text into {$this->language}. If it contains HTML tags, ".
            'preserve them exactly and only translate the visible text between tags. Respond with '.
            'only the translated text (or HTML) and nothing else — no commentary.';
    }
}
