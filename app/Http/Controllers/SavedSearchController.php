<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSavedSearchRequest;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SavedSearchController extends Controller
{
    public function store(StoreSavedSearchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->savedSearches()->create([
            'name' => $data['name'],
            'filters' => collect($data)->except(['name'])->filter()->all(),
        ]);

        return back();
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        Gate::authorize('delete', $savedSearch);

        $savedSearch->delete();

        return back();
    }
}
