<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntryResource;
use App\Models\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EntryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Entry::query()
            ->whereHas('feed', fn ($q) => $q->where('user_id', $user->id))
            ->with('tags:id,name');

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

        return EntryResource::collection($entries);
    }

    public function show(Entry $entry): EntryResource
    {
        Gate::authorize('view', $entry);

        return EntryResource::make($entry->load('feed', 'tags'));
    }
}
