<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteNamesTest extends TestCase
{
    /**
     * Guards against routes/api.php and routes/web.php silently colliding on
     * a route name (e.g. both registering "feeds.index") — since api.php is
     * registered after web.php, a collision overwrites the web route in
     * Laravel's named-route lookup, breaking every route()/Ziggy link that
     * uses that name across the whole app.
     */
    public function test_no_route_name_is_registered_more_than_once(): void
    {
        $seen = [];
        $duplicates = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null) {
                continue;
            }

            if (isset($seen[$name])) {
                $duplicates[$name] = true;
            }

            $seen[$name] = true;
        }

        $this->assertSame([], array_keys($duplicates), 'Duplicate route name(s) found: '.implode(', ', array_keys($duplicates)));
    }

    public function test_web_navigation_route_names_resolve_to_web_paths(): void
    {
        $this->assertSame(url('/feeds'), route('feeds.index'));
        $this->assertSame(url('/entries'), route('entries.index'));
        $this->assertSame(url('/categories'), route('categories.index'));
        $this->assertSame(url('/tags'), route('tags.index'));
    }
}
