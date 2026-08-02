<?php

namespace App\Providers;

use FeedIo\Adapter\Http\Client as FeedIoHttpClient;
use FeedIo\FeedIo;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FeedIo::class, function () {
            return new FeedIo(new FeedIoHttpClient(new GuzzleClient([
                'timeout' => 15,
                'headers' => ['User-Agent' => config('app.name').'/1.0 (+RSS aggregator)'],
            ])));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
