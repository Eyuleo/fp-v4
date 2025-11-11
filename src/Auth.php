<?php

require_once __DIR__ . '/Policies/Policy.php';
require_once __DIR__ . '/Policies/OrderPolicy.php';
require_once __DIR__ . '/Policies/ServicePolicy.php';
require_once __DIR__ . '/Policies/MessagePolicy.php';

/**
 * Auth Helper
 *
 * Provides authorization helpers using policy classes
 */
class Auth
{
    private static ?OrderPolicy $orderPolicy     = null;
    private static ?ServicePolicy $servicePolicy = null;
    private static ?MessagePolicy $messagePolicy = null;

    /**
     * Get the authenticated user as an array
     *
     * @return array|null
     */
    public static function user(): ?array
    {
        if (! isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'    => $_SESSION['user_id'],
            'role'  => $_SESSION['user_role'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
        ];
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get the authenticated user ID
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get the authenticated user role
     *
     * @return string|null
     */
    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }

    /**
     * Authorize an action using a policy
     *
     * @param string $policy The policy class name (e.g., 'order', 'service', 'message')
     * @param string $action The action to authorize (e.g., 'view', 'edit', 'delete')
     * @param mixed ...$args Additional arguments to pass to the policy method
     * @return bool
     * @throws Exception If user is not authenticated or policy/action not found
     */
    public static function authorize(string $policy, string $action, ...$args): bool
    {
        $user = self::user();

        if (! $user) {
            throw new Exception('User must be authenticated to perform authorization checks');
        }

        // Get the policy instance
        $policyInstance = self::getPolicy($policy);

        // Build the method name (e.g., 'canView', 'canEdit')
        $method = 'can' . ucfirst($action);

        if (! method_exists($policyInstance, $method)) {
            throw new Exception("Action '$action' not found in policy '$policy'");
        }

        // Call the policy method with user and additional arguments
        return $policyInstance->$method($user, ...$args);
    }

    /**
     * Authorize an action or throw 403 exception
     *
     * @param string $policy The policy class name
     * @param string $action The action to authorize
     * @param mixed ...$args Additional arguments to pass to the policy method
     * @throws Exception If authorization fails
     */
    public static function authorizeOrFail(string $policy, string $action, ...$args): void
    {
        if (! self::authorize($policy, $action, ...$args)) {
            self::forbidden();
        }
    }

    /**
     * Get a policy instance
     *
     * @param string $policy
     * @return Policy
     * @throws Exception If policy not found
     */
    private static function getPolicy(string $policy): Policy
    {
        switch (strtolower($policy)) {
            case 'order':
                if (self::$orderPolicy === null) {
                    self::$orderPolicy = new OrderPolicy();
                }
                return self::$orderPolicy;

            case 'service':
                if (self::$servicePolicy === null) {
                    self::$servicePolicy = new ServicePolicy();
                }
                return self::$servicePolicy;

            case 'message':
                if (self::$messagePolicy === null) {
                    self::$messagePolicy = new MessagePolicy();
                }
                return self::$messagePolicy;

            default:
                throw new Exception("Policy '$policy' not found");
        }
    }

    /**
     * Return 403 Forbidden response and exit
     */
    private static function forbidden(): void
    {
        http_response_code(403);

        // If it's an AJAX request, return JSON
        if (! empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'You do not have permission to perform this action']);
            exit;
        }

        // Otherwise, show error page
        if (file_exists(__DIR__ . '/../views/errors/403.php')) {
            require __DIR__ . '/../views/errors/403.php';
        } else {
            echo "<h1>403 Forbidden</h1><p>You do not have permission to perform this action.</p>";
        }
        exit;
    }

    /**
     * Check if user can perform an action (returns boolean, doesn't throw)
     *
     * @param string $policy The policy class name
     * @param string $action The action to check
     * @param mixed ...$args Additional arguments to pass to the policy method
     * @return bool
     */
    public static function can(string $policy, string $action, ...$args): bool
    {
        try {
            return self::authorize($policy, $action, ...$args);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user cannot perform an action
     *
     * @param string $policy The policy class name
     * @param string $action The action to check
     * @param mixed ...$args Additional arguments to pass to the policy method
     * @return bool
     */
    public static function cannot(string $policy, string $action, ...$args): bool
    {
        return ! self::can($policy, $action, ...$args);
    }
}
