<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) {

            if ($e->getStatusCode() === 403) {

                return response()->view('pages.absensi.403', [
                    'exception' => $e
                ], 403);

            } else {

                return response()->view('pages.absensi.404', [
                    'exception' => $e
                ], 404);

            }

        });

    })
    ->create();
