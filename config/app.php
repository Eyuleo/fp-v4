<?php

/**
 * Application Configuration
 *
 * Central configuration for application settings
 */

return [
    // Application environment
    'env'        => getenv('APP_ENV') ?: 'development',

    // Debug mode
    'debug'      => getenv('APP_DEBUG') === 'true',

    // Application URL
    'url'        => getenv('APP_URL') ?: 'http://localhost',

    // Timezone configuration
    'timezone'   => getenv('APP_TIMEZONE') ?: 'UTC',

    // Session configuration
    'session'    => [
        'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 7200),
        'secure'   => getenv('SESSION_SECURE') === 'true',
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    // File upload configuration
    'upload'     => [
        'max_size'           => (int) (getenv('MAX_UPLOAD_SIZE') ?: 26214400), // 25MB default
        'path'               => getenv('UPLOAD_PATH') ?: 'storage/uploads',
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'],
    ],

    // Rate limiting
    'rate_limit' => [
        'enabled'        => getenv('RATE_LIMIT_ENABLED') === 'true',
        'login'          => ['attempts' => 5, 'window' => 900],   // 5 attempts per 15 minutes
        'password_reset' => ['attempts' => 10, 'window' => 3600], // 10 per hour
        'search'         => ['attempts' => 100, 'window' => 60],  // 100 per minute
    ],

    // Logging configuration
    'logging'    => [
        'level' => getenv('LOG_LEVEL') ?: 'error',
        'path'  => getenv('LOG_PATH') ?: 'logs',
    ],

    // Stripe configuration
    'stripe'     => [
        'public_key'     => getenv('STRIPE_PUBLIC_KEY'),
        'secret_key'     => getenv('STRIPE_SECRET_KEY'),
        'webhook_secret' => getenv('STRIPE_WEBHOOK_SECRET'),
    ],

    // Email configuration
    'mail'       => [
        'host'         => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port'         => (int) (getenv('MAIL_PORT') ?: 587),
        'username'     => getenv('MAIL_USERNAME'),
        'password'     => getenv('MAIL_PASSWORD'),
        'from_address' => getenv('MAIL_FROM_ADDRESS'),
        'from_name'    => getenv('MAIL_FROM_NAME') ?: 'Student Skills Marketplace',
    ],

    // Platform settings defaults
    'platform'   => [
        'commission_rate' => 15.0, // 15% default
        'max_revisions'   => 3,
    ],
];
