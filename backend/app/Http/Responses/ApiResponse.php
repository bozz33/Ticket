<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ApiResponse
{
    public static function success(array $payload = [], int $status = 200, array $headers = []): JsonResponse
    {
        return response()->json($payload, $status, $headers);
    }

    public static function error(string $message, int $status, array $details = [], array $headers = []): JsonResponse
    {
        $payload = [
            'message' => $message,
            'error' => [
                'code' => self::codeForStatus($status),
            ],
        ];

        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status, $headers);
    }

    public static function validation(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'error' => [
                'code' => 'validation_failed',
            ],
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    private static function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            409 => 'conflict',
            422 => 'validation_failed',
            429 => 'rate_limited',
            default => $status >= 500 ? 'server_error' : 'request_error',
        };
    }
}
