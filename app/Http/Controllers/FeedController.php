<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedRequest;
use App\Http\Requests\UpdateFeedRequest;
use App\Jobs\RefreshFeed;
use App\Models\Feed;
use FeedIo\FeedIo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        ]);

        RefreshFeed::dispatch($feed);

        return back();
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
            'translate_to' => ['nullable', 'string', Rule::in(array_keys(config('translation.languages')))],
        ]));

        return back();
    }
}
