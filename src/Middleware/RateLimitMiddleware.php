<?php

require_once __DIR__ . '/MiddlewareInterface.php';

/**
 * Rate Limit Middleware
 *
 * Prevents abuse by limiting request frequency
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private string $action;
    private int $maxAttempts;
    private int $windowSeconds;

    /**
     * Constructor
     *
     * @param string $action Action identifier (e.g., 'login', 'search')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(string $action = 'general', int $maxAttempts = 60, int $windowSeconds = 60)
    {
        $this->action        = $action;
        $this->maxAttempts   = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Handle the request
     */
    public function handle(callable $next, array $params = [])
    {
        $config = require __DIR__ . '/../../config/app.php';

        if (! $config['rate_limit']['enabled']) {
            return $next();
        }

        $identifier = $this->getIdentifier();

        if ($this->isRateLimited($identifier)) {
            $this->handleRateLimitExceeded();
        }

        $this->incrementAttempts($identifier);

        return $next();
    }

    /**
     * Get unique identifier for rate limiting
     */
    private function getIdentifier(): string
    {
        // Use user ID if authenticated, otherwise IP address
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return $userId ? "user_{$userId}" : "ip_{$ip}";
    }

    /**
     * Check if rate limit is exceeded
     */
    private function isRateLimited(string $identifier): bool
    {
        $key      = $this->getRateLimitKey($identifier);
        $attempts = $this->getAttempts($key);

        return $attempts >= $this->maxAttempts;
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey(string $identifier): string
    {
        $window = floor(time() / $this->windowSeconds);
        return "rate_limit:{$this->action}:{$identifier}:{$window}";
    }

    /**
     * Get current attempt count
     */
    private function getAttempts(string $key): int
    {
        // Try to get from database first
        try {
            $pdo = require __DIR__ . '/../../config/database.php';

            $stmt = $pdo->prepare('
                SELECT attempt_count
                FROM rate_limits
                WHERE rate_key = ? AND expires_at > NOW()
            ');
            $stmt->execute([$key]);
            $result = $stmt->fetch();

            return $result ? (int) $result['attempt_count'] : 0;
        } catch (Exception $e) {
            // Fallback to session-based rate limiting if database not available
            if (! isset($_SESSION['rate_limits'])) {
                $_SESSION['rate_limits'] = [];
            }

            return $_SESSION['rate_limits'][$key] ?? 0;
        }
    }

    /**
     * Increment attempt counter
     */
    private function incrementAttempts(string $identifier): void
    {
        $key = $this->getRateLimitKey($identifier);

        try {
            $pdo = require __DIR__ . '/../../config/database.php';

            $expiresAt = date('Y-m-d H:i:s', time() + $this->windowSeconds);

            $stmt = $pdo->prepare('
                INSERT INTO rate_limits (rate_key, attempt_count, expires_at, created_at)
                VALUES (?, 1, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    attempt_count = attempt_count + 1,
                    expires_at = ?
            ');
            $stmt->execute([$key, $expiresAt, $expiresAt]);
        } catch (Exception $e) {
            // Fallback to session
            if (! isset($_SESSION['rate_limits'])) {
                $_SESSION['rate_limits'] = [];
            }

            $_SESSION['rate_limits'][$key] = ($this->getAttempts($key) ?? 0) + 1;
        }
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(): void
    {
        // Log the event
        error_log(sprintf(
            '[%s] [SECURITY] Rate limit exceeded for action: %s - IP: %s',
            date('Y-m-d H:i:s'),
            $this->action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));

        $retryAfter = $this->windowSeconds;

        http_response_code(429);
        header("Retry-After: {$retryAfter}");

        if (file_exists(__DIR__ . '/../../views/errors/429.php')) {
            require __DIR__ . '/../../views/errors/429.php';
        } else {
            echo '<h1>429 Too Many Requests</h1>';
            echo '<p>You have exceeded the rate limit. Please try again in ' . $retryAfter . ' seconds.</p>';
        }

        exit;
    }

    /**
     * Create rate_limits table if it doesn't exist
     */
    public static function createTable(): void
    {
        try {
            $pdo = require __DIR__ . '/../../config/database.php';

            $pdo->exec('
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    rate_key VARCHAR(255) UNIQUE NOT NULL,
                    attempt_count INT UNSIGNED DEFAULT 0,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_key_expires (rate_key, expires_at)
                ) ENGINE=InnoDB
            ');
        } catch (Exception $e) {
            // Table creation failed, will use session fallback
            error_log('Failed to create rate_limits table: ' . $e->getMessage());
        }
    }
}
