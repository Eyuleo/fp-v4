<?php

require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Admin Controller
 *
 * Handles admin-related HTTP requests
 */
class AdminController
{
    private PDO $db;
    private PaymentRepository $paymentRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->db                = getDatabaseConnection();
        $this->paymentRepository = new PaymentRepository($this->db);
        $this->userRepository    = new UserRepository($this->db);
    }

    /**
     * Show admin dashboard with analytics
     *
     * GET /admin/dashboard
     */
    public function dashboard(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $user = Auth::user();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get date range filter
        $range = $_GET['range'] ?? '30';

        // Calculate date range
        $dateFrom = null;
        $dateTo   = date('Y-m-d');

        switch ($range) {
            case '7':
                $dateFrom = date('Y-m-d', strtotime('-7 days'));
                break;
            case '30':
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
                break;
            case '90':
                $dateFrom = date('Y-m-d', strtotime('-90 days'));
                break;
            case 'custom':
                $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
                $dateTo   = $_GET['date_to'] ?? date('Y-m-d');
                break;
            default:
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
        }

        // Calculate GMV (Gross Merchandise Value) - sum of completed order prices
        $gmvSql = "SELECT COALESCE(SUM(price), 0) as gmv
                   FROM orders
                   WHERE status = 'completed'
                   AND DATE(completed_at) BETWEEN :date_from AND :date_to";
        $gmvStmt = $this->db->prepare($gmvSql);
        $gmvStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $gmv = $gmvStmt->fetch()['gmv'];

        // Calculate total orders and completion rate
        $ordersSql = "SELECT
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                      FROM orders
                      WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
        $ordersStmt = $this->db->prepare($ordersSql);
        $ordersStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $ordersData = $ordersStmt->fetch();

        $totalOrders     = $ordersData['total_orders'];
        $completedOrders = $ordersData['completed_orders'];
        $completionRate  = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        // Calculate on-time delivery rate (completed before deadline)
        $onTimeSql = "SELECT
                        COUNT(*) as total_completed,
                        SUM(CASE WHEN completed_at <= deadline THEN 1 ELSE 0 END) as on_time_completed
                      FROM orders
                      WHERE status = 'completed'
                      AND DATE(completed_at) BETWEEN :date_from AND :date_to";
        $onTimeStmt = $this->db->prepare($onTimeSql);
        $onTimeStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $onTimeData = $onTimeStmt->fetch();

        $onTimeRate = $onTimeData['total_completed'] > 0
            ? ($onTimeData['on_time_completed'] / $onTimeData['total_completed']) * 100
            : 0;

        // Calculate dispute rate
        $disputeSql = "SELECT COUNT(*) as total_disputes
                       FROM disputes
                       WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
        $disputeStmt = $this->db->prepare($disputeSql);
        $disputeStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $totalDisputes = $disputeStmt->fetch()['total_disputes'];

        $disputeRate = $totalOrders > 0 ? ($totalDisputes / $totalOrders) * 100 : 0;

        // Get recent orders for display
        $recentOrdersSql = "SELECT o.id, o.status, o.price, o.created_at,
                                   s.title as service_title,
                                   u_client.email as client_email,
                                   u_student.email as student_email
                            FROM orders o
                            LEFT JOIN services s ON o.service_id = s.id
                            LEFT JOIN users u_client ON o.client_id = u_client.id
                            LEFT JOIN users u_student ON o.student_id = u_student.id
                            WHERE DATE(o.created_at) BETWEEN :date_from AND :date_to
                            ORDER BY o.created_at DESC
                            LIMIT 10";
        $recentOrdersStmt = $this->db->prepare($recentOrdersSql);
        $recentOrdersStmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $recentOrders = $recentOrdersStmt->fetchAll();

        // Prepare analytics data
        $analytics = [
            'gmv'              => $gmv,
            'total_orders'     => $totalOrders,
            'completed_orders' => $completedOrders,
            'completion_rate'  => $completionRate,
            'on_time_rate'     => $onTimeRate,
            'dispute_rate'     => $disputeRate,
            'total_disputes'   => $totalDisputes,
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'range'            => $range,
            'recent_orders'    => $recentOrders,
        ];

        // Render view
        include __DIR__ . '/../../views/admin/dashboard.php';
    }

    /**
     * Show payment history
     *
     * GET /admin/payments
     */
    public function payments(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $user = Auth::user();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get filter parameters
        $status   = $_GET['status'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo   = $_GET['date_to'] ?? null;
        $page     = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        // Build query
        $sql = "SELECT
                    p.*,
                    o.id as order_id,
                    o.status as order_status,
                    s.title as service_title,
                    client.email as client_email,
                    student.email as student_email
                FROM payments p
                INNER JOIN orders o ON p.order_id = o.id
                INNER JOIN services s ON o.service_id = s.id
                INNER JOIN users client ON o.client_id = client.id
                INNER JOIN users student ON o.student_id = student.id
                WHERE 1=1";

        $params = [];

        // Apply status filter
        if ($status && in_array($status, ['pending', 'succeeded', 'refunded', 'partially_refunded', 'failed'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        // Apply date range filter
        if ($dateFrom) {
            $sql .= " AND DATE(p.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(p.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        // Get total count for pagination
        $countSql  = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];
        $totalPages = ceil($totalCount / $perPage);

        // Add ordering and pagination
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        // Execute query
        $stmt = $this->db->prepare($sql);

        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();
        $payments = $stmt->fetchAll();

        // Render view
        include __DIR__ . '/../../views/admin/payments/index.php';
    }

    /**
     * Show user management interface
     *
     * GET /admin/users
     */
    public function users(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $user = Auth::user();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get filter parameters
        $search  = $_GET['search'] ?? null;
        $role    = $_GET['role'] ?? null;
        $status  = $_GET['status'] ?? null;
        $page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 20;

        // Get paginated users
        $result = $this->userRepository->getPaginated($page, $perPage, $search, $role, $status);

        // Extract data
        $users      = $result['users'];
        $totalCount = $result['total'];
        $totalPages = $result['total_pages'];

        // Render view
        include __DIR__ . '/../../views/admin/users/index.php';
    }

    /**
     * Suspend a user
     *
     * POST /admin/users/{id}/suspend
     */
    public function suspendUser(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $adminUser = Auth::user();
        if ($adminUser['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get the user to suspend
        $user = $this->userRepository->findById($id);
        if (! $user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }

        // Prevent suspending admins
        if ($user['role'] === 'admin') {
            $_SESSION['error'] = 'Cannot suspend admin users';
            header('Location: /admin/users/' . $id);
            exit;
        }

        // Prevent suspending yourself
        if ($user['id'] === $adminUser['id']) {
            $_SESSION['error'] = 'Cannot suspend yourself';
            header('Location: /admin/users/' . $id);
            exit;
        }

        // Update user status
        $this->userRepository->updateStatus($id, 'suspended');

        // Log audit entry
        $this->logAudit($adminUser['id'], 'user_suspended', 'user', $id, [
            'old_status' => $user['status'],
            'new_status' => 'suspended',
        ]);

        $_SESSION['success'] = 'User suspended successfully';
        header('Location: /admin/users/' . $id);
        exit;
    }

    /**
     * Reactivate a user
     *
     * POST /admin/users/{id}/reactivate
     */
    public function reactivateUser(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $adminUser = Auth::user();
        if ($adminUser['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get the user to reactivate
        $user = $this->userRepository->findById($id);
        if (! $user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }

        // Update user status
        $this->userRepository->updateStatus($id, 'active');

        // Log audit entry
        $this->logAudit($adminUser['id'], 'user_reactivated', 'user', $id, [
            'old_status' => $user['status'],
            'new_status' => 'active',
        ]);

        $_SESSION['success'] = 'User reactivated successfully';
        header('Location: /admin/users/' . $id);
        exit;
    }

    /**
     * Show user detail view
     *
     * GET /admin/users/{id}
     */
    public function showUser(int $id): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        // Check user is an admin
        $adminUser = Auth::user();
        if ($adminUser['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get the user
        $user = $this->userRepository->findById($id);
        if (! $user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }

        // Get student profile if user is a student
        $studentProfile = null;
        if ($user['role'] === 'student') {
            $stmt = $this->db->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
            $stmt->execute([$id]);
            $studentProfile = $stmt->fetch();
        }

        // Get order history
        $ordersSql = "SELECT o.*, s.title as service_title
                      FROM orders o
                      LEFT JOIN services s ON o.service_id = s.id
                      WHERE o.client_id = :user_id OR o.student_id = :user_id
                      ORDER BY o.created_at DESC
                      LIMIT 20";
        $ordersStmt = $this->db->prepare($ordersSql);
        $ordersStmt->execute(['user_id' => $id]);
        $orders = $ordersStmt->fetchAll();

        // Get review history
        $reviewsSql = "SELECT r.*, o.id as order_id, s.title as service_title
                       FROM reviews r
                       INNER JOIN orders o ON r.order_id = o.id
                       INNER JOIN services s ON o.service_id = s.id
                       WHERE r.client_id = :user_id OR r.student_id = :user_id
                       ORDER BY r.created_at DESC
                       LIMIT 20";
        $reviewsStmt = $this->db->prepare($reviewsSql);
        $reviewsStmt->execute(['user_id' => $id]);
        $reviews = $reviewsStmt->fetchAll();

        // Get services if student
        $services = [];
        if ($user['role'] === 'student') {
            $servicesSql  = "SELECT * FROM services WHERE student_id = :user_id ORDER BY created_at DESC";
            $servicesStmt = $this->db->prepare($servicesSql);
            $servicesStmt->execute(['user_id' => $id]);
            $services = $servicesStmt->fetchAll();
        }

        // Calculate statistics
        $stats = [
            'total_orders'     => count($orders),
            'completed_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'completed')),
            'total_reviews'    => count($reviews),
            'total_services'   => count($services),
        ];

        // Render view
        include __DIR__ . '/../../views/admin/users/show.php';
    }

    /**
     * Log an audit entry
     */
    private function logAudit(int $userId, string $action, string $resourceType, int $resourceId, array $data): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO audit_logs (user_id, action, resource_type, resource_id, old_values, new_values, ip_address, user_agent, created_at)
            VALUES (:user_id, :action, :resource_type, :resource_id, :old_values, :new_values, :ip_address, :user_agent, NOW())
        ');

        $stmt->execute([
            'user_id'       => $userId,
            'action'        => $action,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'old_values'    => json_encode($data['old_status'] ?? null),
            'new_values'    => json_encode($data['new_status'] ?? null),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
