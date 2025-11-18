<?php

/**
 * Helper Functions
 *
 * Common utility functions used throughout the application
 */

/**
 * Escape HTML output to prevent XSS
 */
function e($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get CSRF token
 */
function csrf_token(): string
{
    return CsrfMiddleware::getToken();
}

/**
 * Generate CSRF token field for forms
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Set flash message
 */
function flash(string $type, string $message): void
{
    $_SESSION['alert'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Redirect to a URL
 */
function redirect(string $url, int $statusCode = 302): void
{
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * Get old input value (for form repopulation after validation errors)
 */
function old(string $key, $default = '')
{
    return $_SESSION['old_input'][$key] ?? $default;
}

/**
 * Store old input in session
 */
function flash_input(array $data): void
{
    $_SESSION['old_input'] = $data;
}

/**
 * Clear old input from session
 */
function clear_old_input(): void
{
    unset($_SESSION['old_input']);
}

/**
 * Get configuration value
 */
function config(string $key, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    $keys  = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (! isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value ?? $default;
}

/**
 * Safe number format that handles null values
 * Prevents PHP 8.1+ deprecation warnings when null is passed to number_format
 */
function safe_number_format($number, int $decimals = 2, string $decimal_separator = '.', string $thousands_separator = ','): string
{
    return number_format($number ?? 0, $decimals, $decimal_separator, $thousands_separator);
}

/**
 * Format currency
 */
function currency(?float $amount, string $currency = 'USD'): string
{
    return '$' . safe_number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, string $format = 'M d, Y'): string
{
    if (is_string($date)) {
        $date = new DateTime($date);
    }

    return $date->format($format);
}

/**
 * Check if user is authenticated
 */
function auth(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get authenticated user ID
 */
function user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get authenticated user role
 */
function user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has role
 */
function has_role(string $role): bool
{
    return user_role() === $role;
}

/**
 * Generate a random string
 */
function str_random(int $length = 32): string
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Render a view with layout
 */
function view(string $view, array $data = [], ?string $layout = null): void
{
    // Extract data to variables
    extract($data);

    // Start output buffering
    ob_start();

    // Include the view file
    $viewFile = __DIR__ . '/../views/' . str_replace('.', '/', $view) . '.php';

    if (! file_exists($viewFile)) {
        throw new Exception("View file not found: $viewFile");
    }

    require $viewFile;

    // Get the view content
    $content = ob_get_clean();

    // If layout is specified, wrap content in layout
    if ($layout) {
        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';

        if (! file_exists($layoutFile)) {
            throw new Exception("Layout file not found: $layoutFile");
        }

        require $layoutFile;
    } else {
        echo $content;
    }
}
