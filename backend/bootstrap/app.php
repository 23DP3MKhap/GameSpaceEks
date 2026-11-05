<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\Cors::class);
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'verified' => \App\Http\Middleware\EmailIsVerified::class,
    ]);
})->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response) {
            $messages = [
                401 => 'Lūdzu, piesakies sistēmā',
                403 => 'Piekļuve liegta',
                404 => 'Nav atrasts',
                429 => 'Pārāk daudz mēģinājumu. Mēģini vēlreiz pēc brīža.',
                500 => 'Servera kļūda',
            ];
            $status = $response->getStatusCode();

            if ($response instanceof JsonResponse && isset($messages[$status]) && !($status === 500 && config('app.debug'))) {
                $response->setData(['message' => $messages[$status]]);
            }

            return $response;
        });
    })->create();
