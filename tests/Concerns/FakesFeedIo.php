<?php

namespace Tests\Concerns;

use FeedIo\Adapter\Http\Client as FeedIoHttpClient;
use FeedIo\FeedIo;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

trait FakesFeedIo
{
    /**
     * Bind a FeedIo instance backed by canned HTTP responses.
     *
     * @param  array<int, GuzzleResponse|string>  $responses  each entry is a raw
     *                                                        XML body (200 OK) or a pre-built GuzzleResponse
     */
    protected function fakeFeedIo(array $responses): void
    {
        $queue = array_map(
            fn ($response) => is_string($response) ? new GuzzleResponse(200, [], $response) : $response,
            $responses
        );

        $handlerStack = HandlerStack::create(new MockHandler($queue));
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        $this->app->instance(FeedIo::class, new FeedIo(new FeedIoHttpClient($guzzle)));
    }
}
