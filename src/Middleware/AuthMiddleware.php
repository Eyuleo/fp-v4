<?php

require_once __DIR__ . '/MiddlewareInterface.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Authentication Middleware
 *
 * Ensures user is authenticated before accessing protected routes
 */
class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle the request
     */
    public function handle(callable $next, array $params = [])
    {
        if (! $this->isAuthenticated()) {
            $this->redirectToLogin();
        }

        // Check if user is suspended
        if ($this->isSuspended()) {
            $this->handleSuspendedUser();
        }

        return $next();
    }

    /**
     * Check if user is suspended
     */
    private function isSuspended(): bool
    {
        if (! isset($_SESSION['user_id'])) {
            return false;
        }

        // Get user status from database
        $db   = getDatabaseConnection();
        $stmt = $db->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user && $user['status'] === 'suspended';
    }

    /**
     * Handle suspended user
     */
    private function handleSuspendedUser(): void
    {
        // Log out the user
        self::logout();

        // Set error message
        $_SESSION['error'] = 'Your account has been suspended. Please contact support for more information.';

        // Redirect to login
        header('Location: /login');
        exit;
    }

    /**
     * Check if user is authenticated
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(): void
    {
        // Store intended URL for redirect after login
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];

        header('Location: /login');
        exit;
    }

    /**
     * Get the authenticated user ID
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get the authenticated user role
     */
    public static function getUserRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole(string $role): bool
    {
        return self::getUserRole() === $role;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Set authenticated user
     */
    public static function login(int $userId, string $role, string $email): void
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']    = $userId;
        $_SESSION['user_role']  = $role;
        $_SESSION['user_email'] = $email;
        $_SESSION['login_time'] = time();

        // Generate new CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Log out the user
     */
    public static function logout(): void
    {
        // Clear session data
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();
    }
}
