<?php

require_once __DIR__ . '/MiddlewareInterface.php';
require_once __DIR__ . '/../Auth.php';

/**
 * Service Edit Middleware
 *
 * Validates service editing permissions based on active orders
 */
class ServiceEditMiddleware implements MiddlewareInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = require __DIR__ . '/../../config/database.php';
    }

    /**
     * Handle the request
     *
     * @param callable $next Next middleware in the pipeline
     * @param array $params Route parameters
     * @return mixed
     */
    public function handle(callable $next, array $params = [])
    {
        $user = Auth::user();

        if (!$user) {
            redirect('/login');
            return;
        }

        // Extract service ID from params
        $serviceId = $params['id'] ?? null;

        if (!$serviceId) {
            flash('error', 'Invalid service ID');
            redirect('/student/services');
            return;
        }

        // Check for active orders and store in session for the view/controller
        $activeOrders = $this->getActiveOrdersForService((int)$serviceId);
        
        if (!empty($activeOrders)) {
            $_SESSION['service_has_active_orders'] = true;
            $_SESSION['service_active_orders'] = $activeOrders;
        } else {
            $_SESSION['service_has_active_orders'] = false;
            $_SESSION['service_active_orders'] = [];
        }

        // Continue to next middleware/controller
        return $next($params);
    }

    /**
     * Validate if core fields can be edited
     *
     * @param int $serviceId
     * @return bool
     */
    public function canEditCoreFields(int $serviceId): bool
    {
        $activeOrders = $this->getActiveOrdersForService($serviceId);
        return empty($activeOrders);
    }

    /**
     * Get list of restricted fields when service has active orders
     *
     * @return array
     */
    public function getRestrictedFields(): array
    {
        return ['price', 'delivery_days', 'description', 'category_id'];
    }

    /**
     * Get active orders for a service
     *
     * @param int $serviceId
     * @return array Array of active orders
     */
    public function getActiveOrdersForService(int $serviceId): array
    {
        $sql = "SELECT o.id, o.status, o.deadline, o.created_at,
                       u.name as client_name, u.email as client_email
                FROM orders o
                LEFT JOIN users u ON o.client_id = u.id
                WHERE o.service_id = :service_id
                AND o.status IN ('pending', 'in_progress', 'delivered', 'revision_requested')
                ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['service_id' => $serviceId]);

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check for overdue orders
        $now = new DateTime();
        foreach ($orders as &$order) {
            $deadline = new DateTime($order['deadline']);
            $order['is_overdue'] = ($deadline < $now) && 
                                   in_array($order['status'], ['in_progress', 'revision_requested']);
        }

        return $orders;
    }

    /**
     * Log edit attempt for audit trail
     *
     * @param int $serviceId
     * @param int $userId
     * @param bool $allowed
     * @return void
     */
    public function logEditAttempt(int $serviceId, int $userId, bool $allowed): void
    {
        // Only log blocked attempts for security audit
        if (!$allowed) {
            $sql = "INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, created_at)
                    VALUES (:user_id, 'blocked_edit_attempt', 'service', :service_id, :details, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'details' => json_encode(['reason' => 'active_orders'])
            ]);
        }
    }

    /**
     * Get service by ID
     *
     * @param int $serviceId
     * @return array|null
     */
    private function getService(int $serviceId): ?array
    {
        $sql = "SELECT * FROM services WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $serviceId]);

        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        return $service ?: null;
    }
}
