<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

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
        Schema::defaultStringLength(191);

        Response::macro('success', function ($data = null, $message = 'Success', $status = HttpResponse::HTTP_OK) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], $status);
        });

        Response::macro('error', function ($message = 'Error', $errors = null, $status = HttpResponse::HTTP_BAD_REQUEST) {
            $response = [
                'success' => false,
                'message' => $message
            ];

            if ($errors) {
                $response['errors'] = $errors;
            }

            return response()->json($response, $status);
        });

        Response::macro('notFound', function ($message = 'Resource not found') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], HttpResponse::HTTP_NOT_FOUND);
        });

        Response::macro('unauthorized', function ($message = 'Unauthorized') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], HttpResponse::HTTP_UNAUTHORIZED);
        });

        Response::macro('forbidden', function ($message = 'Forbidden') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], HttpResponse::HTTP_FORBIDDEN);
        });

        Response::macro('validationError', function ($errors, $message = 'Validation failed') {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        });

        Response::macro('serverError', function ($message = 'Internal server error') {
            return response()->json([
                'success' => false,
                'message' => $message
            ], HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        });

        Response::macro('paginated', function ($data, $message = 'Success') {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data->items(),
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total' => $data->total(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'has_more_pages' => $data->hasMorePages()
                ]
            ]);
        });

        Response::macro('created', function ($data = null, $message = 'Resource created successfully') {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], HttpResponse::HTTP_CREATED);
        });

        Response::macro('updated', function ($data = null, $message = 'Resource updated successfully') {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], HttpResponse::HTTP_OK);
        });

        Response::macro('deleted', function ($message = 'Resource deleted successfully') {
            return response()->json([
                'success' => true,
                'message' => $message
            ], HttpResponse::HTTP_OK);
        });
    }
}
