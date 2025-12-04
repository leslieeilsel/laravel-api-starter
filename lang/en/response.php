<?php

return [
    // Success
    'success' => 'Success',

    // Client errors
    'bad_request' => 'Bad Request',
    'unauthorized' => 'Unauthorized, please login first',
    'forbidden' => 'Access Denied',
    'not_found' => 'Resource Not Found',
    'method_not_allowed' => 'Method Not Allowed',
    'validation_error' => 'Validation Failed',
    'too_many_requests' => 'Too Many Requests, please try again later',

    // Server errors
    'server_error' => 'Internal Server Error',
    'service_unavailable' => 'Service Unavailable',

    // User related 1xxx
    'user_not_found' => 'User Not Found',
    'user_disabled' => 'User Has Been Disabled',
    'user_already_exists' => 'User Already Exists',
    'invalid_credentials' => 'Invalid Username or Password',
    'token_expired' => 'Token Expired, please login again',
    'token_invalid' => 'Invalid Token',

    // Resource related 2xxx
    'resource_not_found' => 'Resource Not Found',
    'resource_already_exists' => 'Resource Already Exists',
    'resource_conflict' => 'Resource Conflict',

    // Permission related 3xxx
    'permission_denied' => 'Permission Denied',
    'role_not_found' => 'Role Not Found',

    // Third party 9xxx
    'third_party_error' => 'Third Party Service Error',
    'third_party_timeout' => 'Third Party Service Timeout',
];
