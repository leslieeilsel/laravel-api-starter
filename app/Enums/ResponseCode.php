<?php

namespace App\Enums;

/**
 * API 响应码枚举
 *
 * 规则：
 * - 200: 成功
 * - 4xx: 客户端错误（对应 HTTP 状态码）
 * - 5xx: 服务端错误（对应 HTTP 状态码）
 * - 1xxx: 用户相关业务错误
 * - 2xxx: 资源相关业务错误
 * - 3xxx: 权限相关业务错误
 * - 9xxx: 第三方服务错误
 */
enum ResponseCode: int
{
    // 成功
    case SUCCESS = 200;

    // 客户端错误
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case VALIDATION_ERROR = 422;
    case TOO_MANY_REQUESTS = 429;

    // 服务端错误
    case SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;

    // 用户相关业务错误 1xxx
    case USER_NOT_FOUND = 1001;
    case USER_DISABLED = 1002;
    case USER_ALREADY_EXISTS = 1003;
    case INVALID_CREDENTIALS = 1004;
    case TOKEN_EXPIRED = 1005;
    case TOKEN_INVALID = 1006;

    // 资源相关业务错误 2xxx
    case RESOURCE_NOT_FOUND = 2001;
    case RESOURCE_ALREADY_EXISTS = 2002;
    case RESOURCE_CONFLICT = 2003;

    // 权限相关业务错误 3xxx
    case PERMISSION_DENIED = 3001;
    case ROLE_NOT_FOUND = 3002;

    // 第三方服务错误 9xxx
    case THIRD_PARTY_ERROR = 9001;
    case THIRD_PARTY_TIMEOUT = 9002;

    /**
     * 获取响应码对应的默认消息（支持 i18n）
     */
    public function message(): string
    {
        return __('response.'.$this->key());
    }

    /**
     * 获取语言文件的 key
     */
    public function key(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::BAD_REQUEST => 'bad_request',
            self::UNAUTHORIZED => 'unauthorized',
            self::FORBIDDEN => 'forbidden',
            self::NOT_FOUND => 'not_found',
            self::METHOD_NOT_ALLOWED => 'method_not_allowed',
            self::VALIDATION_ERROR => 'validation_error',
            self::TOO_MANY_REQUESTS => 'too_many_requests',
            self::SERVER_ERROR => 'server_error',
            self::SERVICE_UNAVAILABLE => 'service_unavailable',
            self::USER_NOT_FOUND => 'user_not_found',
            self::USER_DISABLED => 'user_disabled',
            self::USER_ALREADY_EXISTS => 'user_already_exists',
            self::INVALID_CREDENTIALS => 'invalid_credentials',
            self::TOKEN_EXPIRED => 'token_expired',
            self::TOKEN_INVALID => 'token_invalid',
            self::RESOURCE_NOT_FOUND => 'resource_not_found',
            self::RESOURCE_ALREADY_EXISTS => 'resource_already_exists',
            self::RESOURCE_CONFLICT => 'resource_conflict',
            self::PERMISSION_DENIED => 'permission_denied',
            self::ROLE_NOT_FOUND => 'role_not_found',
            self::THIRD_PARTY_ERROR => 'third_party_error',
            self::THIRD_PARTY_TIMEOUT => 'third_party_timeout',
        };
    }

    /**
     * 获取对应的 HTTP 状态码
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::SUCCESS => 200,

            self::BAD_REQUEST => 400,
            self::UNAUTHORIZED, self::INVALID_CREDENTIALS, self::TOKEN_EXPIRED, self::TOKEN_INVALID => 401,
            self::FORBIDDEN, self::PERMISSION_DENIED => 403,
            self::NOT_FOUND, self::USER_NOT_FOUND, self::RESOURCE_NOT_FOUND, self::ROLE_NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::VALIDATION_ERROR => 422,
            self::TOO_MANY_REQUESTS => 429,
            self::USER_DISABLED, self::USER_ALREADY_EXISTS, self::RESOURCE_ALREADY_EXISTS, self::RESOURCE_CONFLICT => 400,

            self::SERVER_ERROR, self::THIRD_PARTY_ERROR => 500,
            self::SERVICE_UNAVAILABLE, self::THIRD_PARTY_TIMEOUT => 503,
        };
    }

    /**
     * 判断是否为成功响应码
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }
}
