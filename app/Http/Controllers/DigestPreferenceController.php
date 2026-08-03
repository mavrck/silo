<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDigestPreferenceRequest;
use Illuminate\Http\RedirectResponse;

class DigestPreferenceController extends Controller
{
    public function update(UpdateDigestPreferenceRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back();
    }
}
