<?php

namespace App\Http\Controllers;

use App\Models\Entry;
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
            ->with('feed:id,title,category_id');

        if ($request->filled('feed_id')) {
            $query->where('feed_id', $request->integer('feed_id'));
        } elseif ($request->filled('category_id')) {
            $categoryId = $request->integer('category_id');
            $query->whereHas('feed', fn ($q) => $q->where('category_id', $categoryId));
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

        return Inertia::render('Entries/Index', [
            'entries' => $entries,
            'sidebar' => $sidebar,
            'filters' => $request->only(['feed_id', 'category_id', 'unread', 'starred']),
        ]);
    }

    public function show(Entry $entry): Response
    {
        Gate::authorize('view', $entry);

        $entry->load('feed:id,title,site_url');
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
}
