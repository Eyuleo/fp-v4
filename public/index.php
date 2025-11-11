<?php

/**
 * Front Controller
 *
 * Single entry point for all HTTP requests
 */

// Start output buffering
ob_start();

// Start session with secure configuration
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => getenv('SESSION_SECURE') === 'true',
    'cookie_samesite' => 'Lax', // Changed from Strict to Lax to allow external redirects
    'use_strict_mode' => true,
]);

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key               = trim($key);
            $value             = trim($value);

            if (! array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Set timezone
date_default_timezone_set('UTC');

// Global exception handler
set_exception_handler(function ($exception) use ($config) {
    // Log the exception
    $logMessage = sprintf(
        "[%s] [EXCEPTION] %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');

    // Clear any output
    if (ob_get_level() > 0) {
        ob_clean();
    }

    // Set appropriate HTTP status code
    http_response_code(500);

    // Show error page
    if ($config['debug']) {
        echo '<h1>Application Error</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        if (file_exists(__DIR__ . '/../views/errors/500.php')) {
            require __DIR__ . '/../views/errors/500.php';
        } else {
            echo '<h1>500 Internal Server Error</h1>';
            echo '<p>An unexpected error occurred. Please try again later.</p>';
        }
    }

    exit;
});

// Global error handler
set_error_handler(function ($severity, $message, $file, $line) {
    if (! (error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Load helper functions
require __DIR__ . '/../src/Helpers.php';

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Autoloader for classes
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../src/';

    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require $file;
        return;
    }

    // Try to find in subdirectories (for classes without namespace)
    $directories = ['', 'Middleware/', 'Controllers/', 'Services/', 'Repositories/', 'Models/', 'Policies/', 'Validators/'];

    foreach ($directories as $dir) {
        $file = $baseDir . $dir . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Initialize router
try {
    $router = new Router();

    // Add global middleware (optional - can be enabled later)
    // $router->addGlobalMiddleware(new CsrfMiddleware());

    // Load routes
    require __DIR__ . '/../routes/web.php';

    // Dispatch request
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    // Exception handler will catch this
    throw $e;
}

// Flush output buffer
ob_end_flush();
