<?php

namespace Tests\Feature;

use App\Agents\TranslateAgent;
use App\Jobs\TranslateEntry;
use App\Models\Entry;
use App\Models\Feed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslateEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_translation_returned_by_the_agent(): void
    {
        TranslateAgent::fake(['Título traducido', '<p>Contenido traducido</p>']);

        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $entry = Entry::factory()->for($feed)->create([
            'title' => 'Translated title source',
            'content' => '<p>Some article content.</p>',
        ]);

        TranslateEntry::dispatchSync($entry);

        $entry->refresh();
        $this->assertSame('Título traducido', $entry->translated_title);
        $this->assertSame('<p>Contenido traducido</p>', $entry->translated_content);
        $this->assertSame('es', $entry->translated_language);
        $this->assertNotNull($entry->translated_at);
    }

    public function test_it_sanitizes_dangerous_html_from_the_translated_content(): void
    {
        TranslateAgent::fake(['Título', '<p>Seguro</p><script>alert(1)</script>']);

        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $entry = Entry::factory()->for($feed)->create([
            'content' => '<p>Safe content.</p>',
        ]);

        TranslateEntry::dispatchSync($entry);

        $translated = (string) $entry->fresh()->translated_content;
        $this->assertStringContainsString('<p>Seguro</p>', $translated);
        $this->assertStringNotContainsString('<script>', $translated);
    }

    public function test_it_does_not_prompt_when_the_feed_has_no_target_language(): void
    {
        TranslateAgent::fake(['should not be used']);

        $feed = Feed::factory()->create(['translate_to' => null]);
        $entry = Entry::factory()->for($feed)->create();

        TranslateEntry::dispatchSync($entry);

        $entry->refresh();
        $this->assertNull($entry->translated_title);
        $this->assertNull($entry->translated_content);
        $this->assertNull($entry->translated_language);
        $this->assertNull($entry->translated_at);
        TranslateAgent::assertNeverPrompted();
    }

    public function test_it_leaves_the_translation_null_when_the_agent_fails(): void
    {
        TranslateAgent::fake(function (): void {
            throw new \RuntimeException('provider unavailable');
        });

        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $entry = Entry::factory()->for($feed)->create([
            'content' => '<p>Some article content.</p>',
        ]);

        TranslateEntry::dispatchSync($entry);

        $entry->refresh();
        $this->assertNull($entry->translated_title);
        $this->assertNull($entry->translated_content);
        $this->assertNull($entry->translated_language);
        $this->assertNull($entry->translated_at);
    }

    public function test_it_does_not_prompt_when_translation_is_globally_disabled(): void
    {
        config(['translation.enabled' => false]);
        TranslateAgent::fake(['should not be used']);

        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $entry = Entry::factory()->for($feed)->create([
            'content' => '<p>Some article content.</p>',
        ]);

        TranslateEntry::dispatchSync($entry);

        $entry->refresh();
        $this->assertNull($entry->translated_title);
        $this->assertNull($entry->translated_content);
        TranslateAgent::assertNeverPrompted();
    }
}
