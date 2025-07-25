<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Response Macros for API responses
        Response::macro('success', function ($data = null, $message = 'Success', $meta = [], $status = 200) {
            $response = [
                'success' => true,
                'message' => $message,
            ];

            if ($data !== null) {
                $response['data'] = $data;
            }

            if (!empty($meta)) {
                $response['meta'] = $meta;
            }

            return Response::json($response, $status);
        });

        Response::macro('error', function ($message = 'Error', $data = null, $status = 400) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if ($data !== null) {
                $response['data'] = $data;
            }

            return Response::json($response, $status);
        });

        Response::macro('notFound', function ($message = 'Resource not found', $data = null) {
            return Response::error($message, $data, 404);
        });

        Response::macro('serverError', function ($message = 'Internal server error', $data = null) {
            return Response::error($message, $data, 500);
        });

        Response::macro('unauthorized', function ($message = 'Unauthorized', $data = null) {
            return Response::error($message, $data, 401);
        });

        Response::macro('forbidden', function ($message = 'Forbidden', $data = null) {
            return Response::error($message, $data, 403);
        });

        Response::macro('validationError', function ($message = 'Validation failed', $errors = []) {
            return Response::json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], 422);
        });

        Response::macro('paginated', function ($paginator, $message = 'Data retrieved successfully', $meta = []) {
            $response = [
                'success' => true,
                'message' => $message,
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'path' => $paginator->path(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ],
            ];

            if (!empty($meta)) {
                $response['meta'] = $meta;
            }

            return Response::json($response);
        });
    }
}
