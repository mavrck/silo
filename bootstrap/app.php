<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetJsonApiContentType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'jsonapi' => SetJsonApiContentType::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            $isJsonApiRoute = $request->is('api/*') && ! $request->is('api/ping');

            if (! $isJsonApiRoute) {
                return null;
            }

            $status = match (true) {
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException => 403,
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => 404,
                $e instanceof ValidationException => 422,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $title = Response::$statusTexts[$status] ?? 'Error';

            $errors = $e instanceof ValidationException
                ? collect($e->errors())
                    ->flatMap(fn (array $messages, string $field) => collect($messages)->map(fn (string $message) => [
                        'status' => (string) $status,
                        'title' => $title,
                        'detail' => $message,
                        'source' => ['pointer' => "/data/attributes/{$field}"],
                    ]))
                    ->values()
                    ->all()
                : [[
                    'status' => (string) $status,
                    'title' => $title,
                    'detail' => $e->getMessage() ?: $title,
                ]];

            return response()->json(['errors' => $errors], $status)
                ->header('Content-Type', 'application/vnd.api+json');
        });
    })->create();
