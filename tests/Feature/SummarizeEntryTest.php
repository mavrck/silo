<?php

namespace Tests\Feature;

use App\Jobs\SummarizeEntry;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Agents\SummarizeAgent;
use Tests\TestCase;

class SummarizeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_summary_returned_by_the_agent(): void
    {
        SummarizeAgent::fake(['This is a concise fake summary of the article.']);

        $entry = Entry::factory()->create([
            'content' => str_repeat('Consensus algorithms coordinate distributed systems. ', 20),
        ]);

        SummarizeEntry::dispatchSync($entry);

        $entry->refresh();
        $this->assertSame('This is a concise fake summary of the article.', $entry->summary);
        $this->assertNotNull($entry->summarized_at);
    }

    public function test_it_strips_html_before_prompting_the_agent(): void
    {
        SummarizeAgent::fake(['fake summary']);

        $entry = Entry::factory()->create([
            'content' => '<p>'.str_repeat('Consensus algorithms coordinate distributed systems. ', 20).'</p><script>alert(1)</script>',
        ]);

        SummarizeEntry::dispatchSync($entry);

        SummarizeAgent::assertPrompted(function ($prompt): bool {
            return ! str_contains($prompt->prompt, '<p>')
                && ! str_contains($prompt->prompt, '<script>');
        });
    }

    public function test_it_skips_entries_that_are_too_short_to_summarize(): void
    {
        SummarizeAgent::fake(['fake summary']);

        $entry = Entry::factory()->create(['content' => 'Too short.']);

        SummarizeEntry::dispatchSync($entry);

        $this->assertNull($entry->fresh()->summary);
        SummarizeAgent::assertNeverPrompted();
    }

    public function test_it_leaves_the_summary_null_when_the_agent_fails(): void
    {
        SummarizeAgent::fake(function (): void {
            throw new \RuntimeException('provider unavailable');
        });

        $entry = Entry::factory()->create([
            'content' => str_repeat('Consensus algorithms coordinate distributed systems. ', 20),
        ]);

        SummarizeEntry::dispatchSync($entry);

        $this->assertNull($entry->fresh()->summary);
    }
}
