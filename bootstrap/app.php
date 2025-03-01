<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {

            if ($request->is('api/*')) {
                $status = 500; // Default to "Internal Server Error"

                // Determine the status code based on the exception type
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $status = 404; // Not Found
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401; // Unauthorized
                } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403; // Forbidden
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422; // Unprocessable Entity
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $status = 405; // Method Not Allowed
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\ThrottleRequestsException) {
                    $status = 429; // Too Many Requests
                }

                // Return a standardized JSON response
                return response()->json([
                    'message' => $e->getMessage(), // Use the message from the exception
                    'status' => (string)$status,   // Ensure status is returned as string
                ], $status);
            }
        });
    })->create();
