<?php

require_once __DIR__ . '/MiddlewareInterface.php';

/**
 * Role Middleware
 *
 * Ensures user has the required role before accessing protected routes
 */
class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    /**
     * Constructor
     *
     * @param array|string $allowedRoles Single role or array of allowed roles
     */
    public function __construct($allowedRoles)
    {
        $this->allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    }

    /**
     * Handle the request
     */
    public function handle(callable $next, array $params = [])
    {
        // Check if user is authenticated
        if (! isset($_SESSION['user_id']) || ! isset($_SESSION['user_role'])) {
            $this->forbidden('You must be logged in to access this resource');
        }

        $userRole = $_SESSION['user_role'];

        // Check if user has one of the allowed roles
        if (! in_array($userRole, $this->allowedRoles)) {
            $this->forbidden('You do not have permission to access this resource');
        }

        return $next();
    }

    /**
     * Return 403 Forbidden response
     */
    private function forbidden(string $message): void
    {
        http_response_code(403);

        // If it's an AJAX request, return JSON
        if (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
            exit;
        }

        // Otherwise, show error page
        if (file_exists(__DIR__ . '/../../views/errors/403.php')) {
            require __DIR__ . '/../../views/errors/403.php';
        } else {
            echo "<h1>403 Forbidden</h1><p>$message</p>";
        }
        exit;
    }
}
