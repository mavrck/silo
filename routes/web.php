<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DigestPreferenceController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\OpmlController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register') && config('app.registrations_enabled'),
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/profile/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::post('/profile/api-tokens/{token}/regenerate', [ApiTokenController::class, 'regenerate'])->name('api-tokens.regenerate');
    Route::delete('/profile/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    Route::patch('/profile/digest', [DigestPreferenceController::class, 'update'])->name('digest-preference.update');

    Route::resource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('feeds', FeedController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::patch('/feeds/{feed}/summarize', [FeedController::class, 'toggleSummarize'])->name('feeds.summarize');
    Route::patch('/feeds/{feed}/translate', [FeedController::class, 'updateTranslation'])->name('feeds.translate');
    Route::patch('/feeds/{feed}/digest', [FeedController::class, 'updateDigest'])->name('feeds.digest');
    Route::patch('/feeds/{feed}/mark-all-read', [FeedController::class, 'markAllRead'])->name('feeds.mark-all-read');

    Route::get('/opml/export', [OpmlController::class, 'export'])->name('opml.export');
    Route::post('/opml/import', [OpmlController::class, 'import'])->name('opml.import');

    Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::patch('/entries/mark-all-read', [EntryController::class, 'markAllRead'])->name('entries.mark-all-read');
    Route::get('/entries/{entry}', [EntryController::class, 'show'])->name('entries.show');
    Route::patch('/entries/{entry}/read', [EntryController::class, 'markRead'])->name('entries.read');
    Route::patch('/entries/{entry}/unread', [EntryController::class, 'markUnread'])->name('entries.unread');
    Route::patch('/entries/{entry}/star', [EntryController::class, 'toggleStar'])->name('entries.star');
    Route::post('/entries/{entry}/tags', [EntryController::class, 'attachTag'])->name('entries.tags.attach');
    Route::delete('/entries/{entry}/tags/{tag}', [EntryController::class, 'detachTag'])->name('entries.tags.detach');

    Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
    Route::patch('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
});

require __DIR__.'/auth.php';
