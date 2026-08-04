<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Translation Enabled
    |--------------------------------------------------------------------------
    |
    | A global kill switch for per-feed AI translation, independent of any
    | individual feed's setting. Turning this off stops new translation
    | API calls from being dispatched and prevents setting a target
    | language on a feed, without touching translations already stored.
    |
    */

    'enabled' => (bool) env('TRANSLATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Supported Translation Languages
    |--------------------------------------------------------------------------
    |
    | The single source of truth for which languages a feed can be
    | translated into. Used for both backend validation and the frontend
    | dropdown, so the two can never drift out of sync.
    |
    */

    'languages' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'nl' => 'Dutch',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese (Simplified)',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'pl' => 'Polish',
        'tr' => 'Turkish',
        'vi' => 'Vietnamese',
        'sv' => 'Swedish',
    ],

];
