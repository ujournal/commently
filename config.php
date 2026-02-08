<?php

return [
    'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'flarum',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
        'prefix' => getenv('DB_PREFIX') ?: '',
        'strict' => filter_var(getenv('DB_STRICT') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'engine' => getenv('DB_ENGINE') ?: 'InnoDB',
        'prefix_indexes' => true,
    ],
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'paths' => [
        'api' => getenv('APP_API_PATH') ?: 'api',
        'admin' => getenv('APP_ADMIN_PATH') ?: 'admin',
    ],
    'headers' => [
        'poweredByHeader' => filter_var(getenv('APP_HEADER_POWERED_BY') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'referrerPolicy' => getenv('APP_REFERRER_POLICY') ?: 'same-origin',
    ],
];
