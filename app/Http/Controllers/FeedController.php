<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedRequest;
use App\Http\Requests\UpdateFeedRequest;
use App\Jobs\RefreshFeed;
use App\Models\Feed;
use FeedIo\FeedIo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FeedController extends Controller
{
    public function index(): Response
    {
        $categories = auth()->user()
            ->categories()
            ->with(['feeds' => fn ($query) => $query->orderBy('title')])
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        return Inertia::render('Feeds/Index', [
            'categories' => $categories,
            'languages' => config('translation.languages'),
            'translationEnabled' => config('translation.enabled'),
        ]);
    }

    public function store(StoreFeedRequest $request, FeedIo $feedIo): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $result = $feedIo->read($data['url']);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'url' => 'This URL could not be read as a feed. Double-check it and try again.',
            ]);
        }

        $feedData = $result->getFeed();

        $category = ! empty($data['category_id'])
            ? $user->categories()->findOrFail($data['category_id'])
            : $user->categories()->firstOrCreate(['name' => 'Uncategorized']);

        $feed = $user->feeds()->create([
            'category_id' => $category->id,
            'title' => trim($data['title'] ?? '') ?: ($feedData->getTitle() ?? $data['url']),
            'url' => $data['url'],
            'site_url' => $feedData->getLink(),
            'description' => $feedData->getDescription(),
            'summarize' => $data['summarize'] ?? false,
            'translate_to' => $data['translate_to'] ?? null,
            'digest_frequency' => $data['digest_frequency'] ?? 'off',
        ]);

        RefreshFeed::dispatch($feed);

        return back();
    }

    public function show(Request $request, Feed $feed): Response
    {
        Gate::authorize('view', $feed);

        $query = $this->filteredEntriesQuery($request, $feed)->with('tags:id,name');

        if ($request->boolean('unread')) {
            $query->unread();
        }

        $entries = $query->orderByDesc('published_at')
            ->paginate(25)
            ->withQueryString();

        $user = $request->user();

        $sidebar = $user->categories()
            ->with(['feeds' => fn ($q) => $q->withCount([
                'entries as unread_count' => fn ($q2) => $q2->unread(),
            ])])
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        $tags = $user->tags()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Feeds/Show', [
            'feed' => $feed->load('category:id,name'),
            'entries' => $entries,
            'sidebar' => $sidebar,
            'tags' => $tags,
            'filters' => $request->only(['q', 'unread', 'starred']),
            'unreadCount' => $this->filteredEntriesQuery($request, $feed)->unread()->count(),
        ]);
    }

    public function update(UpdateFeedRequest $request, Feed $feed): RedirectResponse
    {
        $feed->update($request->validated());

        return back();
    }

    public function destroy(Feed $feed): RedirectResponse
    {
        Gate::authorize('delete', $feed);

        $feed->delete();

        return back();
    }

    public function toggleSummarize(Feed $feed): RedirectResponse
    {
        Gate::authorize('update', $feed);

        $feed->update(['summarize' => ! $feed->summarize]);

        return back();
    }

    public function updateTranslation(Request $request, Feed $feed): RedirectResponse
    {
        Gate::authorize('update', $feed);

        $feed->update($request->validate([
            'translate_to' => config('translation.enabled')
                ? ['nullable', 'string', Rule::in(array_keys(config('translation.languages')))]
                : ['prohibited'],
        ]));

        return back();
    }

    public function updateDigest(Request $request, Feed $feed): RedirectResponse
    {
        Gate::authorize('update', $feed);

        $feed->update($request->validate([
            'digest_frequency' => ['required', Rule::in(['off', 'daily', 'weekly'])],
        ]));

        return back();
    }

    public function markAllRead(Request $request, Feed $feed): RedirectResponse
    {
        Gate::authorize('view', $feed);

        $count = $this->filteredEntriesQuery($request, $feed)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('status', $count > 0
            ? 'Marked '.$count.' '.Str::plural('entry', $count).' as read.'
            : 'No unread entries to mark as read.');
    }

    /**
     * The given feed's entries matching the request's search/starred
     * filters. Shared between show() and markAllRead() so both agree on
     * exactly which entries are "currently in view".
     */
    private function filteredEntriesQuery(Request $request, Feed $feed): HasMany
    {
        $query = $feed->entries();

        if ($request->filled('q')) {
            $query->search($request->string('q')->value());
        }

        if ($request->boolean('starred')) {
            $query->starred();
        }

        return $query;
    }
}
