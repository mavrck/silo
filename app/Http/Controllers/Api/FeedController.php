<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedResource;
use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FeedController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $feeds = $request->user()
            ->feeds()
            ->with('category')
            ->orderBy('title')
            ->get();

        return FeedResource::collection($feeds);
    }

    public function show(Feed $feed): FeedResource
    {
        Gate::authorize('view', $feed);

        return FeedResource::make($feed->load('category'));
    }
}
