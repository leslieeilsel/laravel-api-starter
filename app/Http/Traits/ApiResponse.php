<?php

namespace App\Http\Traits;

use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    /**
     * 成功响应
     */
    protected function success(mixed $data = null, ?string $message = null): JsonResponse
    {
        return $this->response(ResponseCode::SUCCESS, $data, $message);
    }

    /**
     * 失败响应
     */
    protected function fail(ResponseCode $code, ?string $message = null, mixed $errors = null): JsonResponse
    {
        $response = [
            'code' => $code->value,
            'message' => $message ?? $code->message(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code->httpStatus());
    }

    /**
     * 未授权响应
     */
    protected function unauthorized(?string $message = null): JsonResponse
    {
        return $this->fail(ResponseCode::UNAUTHORIZED, $message);
    }

    /**
     * 通用响应构建
     */
    protected function response(ResponseCode $code, mixed $data = null, ?string $message = null): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        $response = [
            'code' => $code->value,
            'message' => $message ?? $code->message(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code->httpStatus());
    }
}
