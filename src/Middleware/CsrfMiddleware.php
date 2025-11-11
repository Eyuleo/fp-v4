<?php

require_once __DIR__ . '/MiddlewareInterface.php';

/**
 * CSRF Middleware
 *
 * Protects against Cross-Site Request Forgery attacks
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(callable $next, array $params = [])
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Only check CSRF token for mutating requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->validateCsrfToken();
        }

        // Generate token if not exists
        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $this->generateToken();
        }

        return $next();
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (! $token) {
            $this->handleInvalidToken('CSRF token missing');
        }

        if (! isset($_SESSION['csrf_token'])) {
            $this->handleInvalidToken('Session expired');
        }

        if (! hash_equals($_SESSION['csrf_token'], $token)) {
            $this->handleInvalidToken('Invalid CSRF token');
        }
    }

    /**
     * Generate a new CSRF token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Handle invalid token
     */
    private function handleInvalidToken(string $message): void
    {
        // Log the attempt
        error_log(sprintf(
            '[%s] [SECURITY] CSRF validation failed: %s - IP: %s',
            date('Y-m-d H:i:s'),
            $message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));

        http_response_code(403);

        if (file_exists(__DIR__ . '/../../views/errors/403.php')) {
            require __DIR__ . '/../../views/errors/403.php';
        } else {
            echo '<h1>403 Forbidden</h1>';
            echo '<p>CSRF token validation failed. Please refresh the page and try again.</p>';
        }

        exit;
    }

    /**
     * Get the current CSRF token
     */
    public static function getToken(): string
    {
        if (! isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}
