<?php

use App\Enums\ResponseCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 仅对 API 请求返回统一 JSON 格式
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // 验证异常
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::VALIDATION_ERROR;

                return response()->json([
                    'code' => $code->value,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $code->httpStatus());
            }
        });

        // 认证异常
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::UNAUTHORIZED;

                return response()->json([
                    'code' => $code->value,
                    'message' => $code->message(),
                ], $code->httpStatus());
            }
        });

        // 403 禁止访问
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::FORBIDDEN;

                return response()->json([
                    'code' => $code->value,
                    'message' => $e->getMessage() ?: $code->message(),
                ], $code->httpStatus());
            }
        });

        // 404 资源不存在
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::NOT_FOUND;

                return response()->json([
                    'code' => $code->value,
                    'message' => $code->message(),
                ], $code->httpStatus());
            }
        });

        // 405 方法不允许
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::METHOD_NOT_ALLOWED;

                return response()->json([
                    'code' => $code->value,
                    'message' => $code->message(),
                ], $code->httpStatus());
            }
        });

        // 429 请求过多
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $code = ResponseCode::TOO_MANY_REQUESTS;

                return response()->json([
                    'code' => $code->value,
                    'message' => $code->message(),
                ], $code->httpStatus());
            }
        });
    })->create();
