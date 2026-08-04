<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Entry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $this->filteredEntriesQuery($request, $user)
            ->with(['feed:id,title,category_id', 'tags:id,name']);

        if ($request->boolean('unread')) {
            $query->unread();
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
            'unreadCount' => $this->filteredEntriesQuery($request, $user)->unread()->count(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $count = $this->filteredEntriesQuery($request, $request->user())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('status', $count > 0
            ? 'Marked '.$count.' '.Str::plural('entry', $count).' as read.'
            : 'No unread entries to mark as read.');
    }

    /**
     * The entries matching the request's feed/category/tag/search/starred
     * filters, scoped to the given user. Shared between the reader's index
     * listing and the "mark all as read" bulk action so both agree on
     * exactly which entries are "currently in view".
     */
    private function filteredEntriesQuery(Request $request, User $user): Builder
    {
        $query = Entry::query()
            ->whereHas('feed', fn ($q) => $q->where('user_id', $user->id));

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

        if ($request->boolean('starred')) {
            $query->starred();
        }

        return $query;
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
