<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiTokenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $newToken = $request->user()->createToken($request->validated('name'));

        return back()->with('token', $newToken->plainTextToken);
    }

    public function regenerate(Request $request, int $token): RedirectResponse
    {
        $existing = $request->user()->tokens()->findOrFail($token);
        $name = $existing->name;
        $existing->delete();

        $newToken = $request->user()->createToken($name);

        return back()->with('token', $newToken->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->findOrFail($token)->delete();

        return back();
    }
}
