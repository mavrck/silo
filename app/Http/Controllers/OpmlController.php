<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOpmlRequest;
use App\Services\Opml\OpmlExporter;
use App\Services\Opml\OpmlImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class OpmlController extends Controller
{
    public function export(OpmlExporter $exporter): Response
    {
        $xml = $exporter->export(auth()->user());

        return response($xml, 200, [
            'Content-Type' => 'text/x-opml',
            'Content-Disposition' => 'attachment; filename="feeds.opml"',
        ]);
    }

    public function import(ImportOpmlRequest $request, OpmlImporter $importer): RedirectResponse
    {
        try {
            $created = $importer->import($request->user(), $request->file('file')->get());
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'file' => 'This file could not be read as OPML. Double-check the export and try again.',
            ]);
        }

        return back()->with('status', "Imported {$created} new feed(s).");
    }
}
