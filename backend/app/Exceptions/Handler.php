<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse('Validation failed', 422, $e->errors());
            }
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse('Unauthenticated', 401);
            }
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse('Forbidden', 403);
            }
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse('Resource not found', 404);
            }
        });

        $this->renderable(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse(
                    $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error',
                    $e->getStatusCode()
                );
            }
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return $this->errorResponse('Internal server error', 500);
            }
        });
    }
}