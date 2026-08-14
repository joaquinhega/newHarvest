<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function errorResponse(
        string $message = 'Error',
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status_code' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function paginatedResponse(
        Paginator|LengthAwarePaginator $paginator,
        string $message = 'OK',
        int $status = 200
    ): JsonResponse {
        return $this->successResponse(
            $paginator->items(),
            $message,
            $status,
            [
                'pagination' => [
                    'current_page' => method_exists($paginator, 'currentPage') ? $paginator->currentPage() : null,
                    'per_page' => $paginator->perPage(),
                    'total' => method_exists($paginator, 'total') ? $paginator->total() : null,
                    'last_page' => method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null,
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ]
        );
    }
}