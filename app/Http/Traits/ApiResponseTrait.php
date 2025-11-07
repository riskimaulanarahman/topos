<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    /**
     * Success response with data
     */
    protected function successResponse($data = null, $message = 'Success', $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse($message = 'Error', $errors = null, $statusCode = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors, $message = 'Validation failed')
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden')
    {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Server error response
     */
    protected function serverErrorResponse($message = 'Internal server error')
    {
        return $this->errorResponse($message, null, 500);
    }

    /**
     * Conflict response (for business rule violations)
     */
    protected function conflictResponse($message = 'Conflict')
    {
        return $this->errorResponse($message, null, 409);
    }
}
