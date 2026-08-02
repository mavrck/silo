<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\OpmlController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('feeds', FeedController::class)
        ->only(['index', 'store', 'destroy']);

    Route::get('/opml/export', [OpmlController::class, 'export'])->name('opml.export');
    Route::post('/opml/import', [OpmlController::class, 'import'])->name('opml.import');

    Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::get('/entries/{entry}', [EntryController::class, 'show'])->name('entries.show');
    Route::patch('/entries/{entry}/read', [EntryController::class, 'markRead'])->name('entries.read');
    Route::patch('/entries/{entry}/unread', [EntryController::class, 'markUnread'])->name('entries.unread');
    Route::patch('/entries/{entry}/star', [EntryController::class, 'toggleStar'])->name('entries.star');
});

require __DIR__.'/auth.php';
