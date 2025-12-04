<?php

return [
    // 成功
    'success' => '操作成功',

    // 客户端错误
    'bad_request' => '请求参数错误',
    'unauthorized' => '未授权，请先登录',
    'forbidden' => '无权限访问',
    'not_found' => '资源不存在',
    'method_not_allowed' => '请求方法不允许',
    'validation_error' => '数据验证失败',
    'too_many_requests' => '请求过于频繁，请稍后再试',

    // 服务端错误
    'server_error' => '服务器内部错误',
    'service_unavailable' => '服务暂不可用',

    // 用户相关 1xxx
    'user_not_found' => '用户不存在',
    'user_disabled' => '用户已被禁用',
    'user_already_exists' => '用户已存在',
    'invalid_credentials' => '用户名或密码错误',
    'token_expired' => '登录已过期，请重新登录',
    'token_invalid' => '无效的令牌',

    // 资源相关 2xxx
    'resource_not_found' => '资源不存在',
    'resource_already_exists' => '资源已存在',
    'resource_conflict' => '资源冲突',

    // 权限相关 3xxx
    'permission_denied' => '权限不足',
    'role_not_found' => '角色不存在',

    // 第三方服务 9xxx
    'third_party_error' => '第三方服务异常',
    'third_party_timeout' => '第三方服务超时',
];
