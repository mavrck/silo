<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tags = $request->user()
            ->tags()
            ->withCount('entries')
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function show(Tag $tag): TagResource
    {
        Gate::authorize('view', $tag);

        return TagResource::make($tag->loadCount('entries'));
    }
}
