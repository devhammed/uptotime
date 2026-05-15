<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->prefersJsonResponses()
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            '*',
            SymfonyRequest::HEADER_X_FORWARDED_FOR
            | SymfonyRequest::HEADER_X_FORWARDED_HOST
            | SymfonyRequest::HEADER_X_FORWARDED_PORT
            | SymfonyRequest::HEADER_X_FORWARDED_PROTO
            | SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB
            | SymfonyRequest::HEADER_X_FORWARDED_TRAEFIK,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontTruncateRequestExceptions();

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && ($previous = $e->getPrevious()) && ($previous instanceof ModelNotFoundException)) {
                $model = Str::afterLast($previous->getModel(), '\\');

                return response()->json([
                    'message' => __('pagination.not_found', ['model' => $model]),
                ], 404);
            }

            return null;
        });
    })
    ->create();
