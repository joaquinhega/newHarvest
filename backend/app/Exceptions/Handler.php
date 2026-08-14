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

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $e->errors()
            );
        });

        $this->renderable(function (AuthenticationException $e, Request $request) {
            return $this->errorResponse('Unauthenticated', 401);
        });

        $this->renderable(function (AuthorizationException $e, Request $request) {
            return $this->errorResponse('Forbidden', 403);
        });

        $this->renderable(function (ModelNotFoundException $e, Request $request) {
            return $this->errorResponse('Resource not found', 404);
        });

        $this->renderable(function (HttpExceptionInterface $e, Request $request) {
            return $this->errorResponse(
                $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error',
                $e->getStatusCode()
            );
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->errorResponse('Internal server error', 500);
        });
    }
}
