<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Entry;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Entry::query()
            ->whereHas('feed', fn ($q) => $q->where('user_id', $user->id))
            ->with(['feed:id,title,category_id', 'tags:id,name']);

        if ($request->filled('feed_id')) {
            $query->where('feed_id', $request->integer('feed_id'));
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->integer('category_id');
            $query->whereHas('feed', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($request->filled('tag_id')) {
            $tagId = $request->integer('tag_id');
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
        }

        if ($request->filled('q')) {
            $query->search($request->string('q')->value());
        }

        if ($request->boolean('unread')) {
            $query->unread();
        }

        if ($request->boolean('starred')) {
            $query->starred();
        }

        $entries = $query->orderByDesc('published_at')
            ->paginate(25)
            ->withQueryString();

        $sidebar = $user->categories()
            ->with(['feeds' => fn ($q) => $q->withCount([
                'entries as unread_count' => fn ($q2) => $q2->unread(),
            ])])
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        $tags = $user->tags()->orderBy('name')->get(['id', 'name']);
        $savedSearches = $user->savedSearches()->orderBy('name')->get(['id', 'name', 'filters']);

        return Inertia::render('Entries/Index', [
            'entries' => $entries,
            'sidebar' => $sidebar,
            'tags' => $tags,
            'savedSearches' => $savedSearches,
            'filters' => $request->only(['feed_id', 'category_id', 'tag_id', 'q', 'unread', 'starred']),
        ]);
    }

    public function show(Entry $entry): Response
    {
        Gate::authorize('view', $entry);

        $entry->load('feed:id,title,site_url', 'tags:id,name');
        $entry->markRead();

        return Inertia::render('Entries/Show', [
            'entry' => $entry,
        ]);
    }

    public function markRead(Entry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);
        $entry->markRead();

        return back();
    }

    public function markUnread(Entry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);
        $entry->markUnread();

        return back();
    }

    public function toggleStar(Entry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);
        $entry->toggleStarred();

        return back();
    }

    public function attachTag(StoreTagRequest $request, Entry $entry): RedirectResponse
    {
        Gate::authorize('update', $entry);

        $tag = $request->user()->tags()->firstOrCreate([
            'name' => $request->validated('name'),
        ]);

        $entry->tags()->syncWithoutDetaching($tag);

        return back();
    }

    public function detachTag(Entry $entry, Tag $tag): RedirectResponse
    {
        Gate::authorize('update', $entry);

        $entry->tags()->detach($tag);

        return back();
    }
}
