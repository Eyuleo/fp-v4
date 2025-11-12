<?php

require_once __DIR__ . '/../Repositories/PaymentRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/DisputeRepository.php';
require_once __DIR__ . '/../Repositories/CategoryRepository.php';
require_once __DIR__ . '/../Services/NotificationService.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
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
    private ServiceRepository $serviceRepository;
    private DisputeRepository $disputeRepository;
    private CategoryRepository $categoryRepository;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->db                 = getDatabaseConnection();
        $this->paymentRepository  = new PaymentRepository($this->db);
        $this->userRepository     = new UserRepository($this->db);
        $this->serviceRepository  = new ServiceRepository($this->db);
        $this->disputeRepository  = new DisputeRepository($this->db);
        $this->categoryRepository = new CategoryRepository($this->db);

        // Initialize notification service
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($this->db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
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
     * Show order management interface
     *
     * GET /admin/orders
     */
    public function orders(): void
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
        $sql = "SELECT o.*,
                       s.title as service_title,
                       client.email as client_email, client.name as client_name,
                       student.email as student_email, student.name as student_name
                FROM orders o
                INNER JOIN services s ON o.service_id = s.id
                INNER JOIN users client ON o.client_id = client.id
                INNER JOIN users student ON o.student_id = student.id
                WHERE 1=1";

        $params = [];

        // Apply status filter
        if ($status && in_array($status, ['pending', 'in_progress', 'delivered', 'revision_requested', 'completed', 'cancelled'])) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }

        // Apply date range filter
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        // Get total count for pagination
        $countSql  = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];
        $totalPages = ceil($totalCount / $perPage);

        // Add ordering and pagination
        $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
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
        $orders = $stmt->fetchAll();

        // Render view
        include __DIR__ . '/../../views/admin/orders/index.php';
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
                      WHERE o.client_id = ? OR o.student_id = ?
                      ORDER BY o.created_at DESC
                      LIMIT 20";
        $ordersStmt = $this->db->prepare($ordersSql);
        $ordersStmt->execute([$id, $id]);
        $orders = $ordersStmt->fetchAll();

        // Get review history
        $reviewsSql = "SELECT r.*, o.id as order_id, s.title as service_title
                       FROM reviews r
                       INNER JOIN orders o ON r.order_id = o.id
                       INNER JOIN services s ON o.service_id = s.id
                       WHERE r.client_id = ? OR r.student_id = ?
                       ORDER BY r.created_at DESC
                       LIMIT 20";
        $reviewsStmt = $this->db->prepare($reviewsSql);
        $reviewsStmt->execute([$id, $id]);
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
     * Deactivate a service
     *
     * POST /admin/services/{id}/deactivate
     */
    public function deactivateService(int $id): void
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

        // Get the service
        $service = $this->serviceRepository->findById($id);
        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /admin/services');
            exit;
        }

        // Get reason from POST data
        $reason = $_POST['reason'] ?? 'Service deactivated by admin';

        // Update service status to inactive
        $this->serviceRepository->update($id, ['status' => 'inactive']);

        // Get student details
        $student = $this->userRepository->findById($service['student_id']);

        // Send notification to student
        if ($student) {
            $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

            $this->notificationService->notify(
                $student['id'],
                $student['email'],
                'service_deactivated',
                'Service Deactivated',
                "Your service '{$service['title']}' has been deactivated by an administrator",
                'emails/service-moderation',
                [
                    'student_name'  => $student['name'] ?? $student['email'],
                    'service_title' => $service['title'],
                    'action'        => 'deactivated',
                    'reason'        => $reason,
                    'service_url'   => $appUrl . '/student/services/' . $id . '/edit',
                ],
                $appUrl . '/student/services'
            );
        }

        // Log audit entry
        $this->logAudit($adminUser['id'], 'service_deactivated', 'service', $id, [
            'old_status' => $service['status'],
            'new_status' => 'inactive',
            'reason'     => $reason,
        ]);

        $_SESSION['success'] = 'Service deactivated successfully';
        header('Location: /admin/services/' . $id);
        exit;
    }

    /**
     * Activate a service
     *
     * POST /admin/services/{id}/activate
     */
    public function activateService(int $id): void
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

        // Get the service
        $service = $this->serviceRepository->findById($id);
        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /admin/services');
            exit;
        }

        // Check if service is already active
        if ($service['status'] === 'active') {
            $_SESSION['error'] = 'Service is already active';
            header('Location: /admin/services/' . $id);
            exit;
        }

        // Update service status to active
        $this->serviceRepository->update($id, ['status' => 'active']);

        // Get student details
        $student = $this->userRepository->findById($service['student_id']);

        // Send notification to student
        if ($student) {
            $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

            $this->notificationService->notify(
                $student['id'],
                $student['email'],
                'service_activated',
                'Service Activated',
                "Your service '{$service['title']}' has been activated by an administrator",
                'emails/service-moderation',
                [
                    'student_name'  => $student['name'] ?? $student['email'],
                    'service_title' => $service['title'],
                    'action'        => 'activated',
                    'reason'        => 'Your service has been reviewed and approved',
                    'service_url'   => $appUrl . '/services/' . $id,
                ],
                $appUrl . '/services/' . $id
            );
        }

        // Log audit entry
        $this->logAudit($adminUser['id'], 'service_activated', 'service', $id, [
            'old_status' => $service['status'],
            'new_status' => 'active',
        ]);

        $_SESSION['success'] = 'Service activated successfully. The student has been notified.';
        header('Location: /admin/services/' . $id);
        exit;
    }

    /**
     * Delete a service
     *
     * POST /admin/services/{id}/delete
     */
    public function deleteService(int $id): void
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

        // Get the service
        $service = $this->serviceRepository->findById($id);
        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /admin/services');
            exit;
        }

        // Check if service has active orders
        if ($this->serviceRepository->hasActiveOrders($id)) {
            $_SESSION['error'] = 'Cannot delete service with active orders';
            header('Location: /admin/services/' . $id);
            exit;
        }

        // Get reason from POST data
        $reason = $_POST['reason'] ?? 'Service deleted by admin';

        // Get student details before deletion
        $student = $this->userRepository->findById($service['student_id']);

        // Delete service files
        if (! empty($service['sample_files'])) {
            $uploadDir = __DIR__ . '/../../storage/uploads/services/' . $id . '/';
            if (is_dir($uploadDir)) {
                $this->deleteDirectory($uploadDir);
            }
        }

        // Delete the service
        $this->serviceRepository->delete($id);

        // Send notification to student
        if ($student) {
            $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

            $this->notificationService->notify(
                $student['id'],
                $student['email'],
                'service_deleted',
                'Service Deleted',
                "Your service '{$service['title']}' has been deleted by an administrator",
                'emails/service-moderation',
                [
                    'student_name'  => $student['name'] ?? $student['email'],
                    'service_title' => $service['title'],
                    'action'        => 'deleted',
                    'reason'        => $reason,
                    'service_url'   => $appUrl . '/student/services',
                ],
                $appUrl . '/student/services'
            );
        }

        // Log audit entry
        $this->logAudit($adminUser['id'], 'service_deleted', 'service', $id, [
            'old_status' => $service['status'],
            'new_status' => 'deleted',
            'reason'     => $reason,
            'title'      => $service['title'],
        ]);

        $_SESSION['success'] = 'Service deleted successfully';
        header('Location: /admin/services');
        exit;
    }

    /**
     * Show service detail view for admin
     *
     * GET /admin/services/{id}
     */
    public function showService(int $id): void
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

        // Get the service with student details
        $service = $this->serviceRepository->findById($id);
        if (! $service) {
            $_SESSION['error'] = 'Service not found';
            header('Location: /admin/services');
            exit;
        }

        // Get student details
        $student = $this->userRepository->findById($service['student_id']);

        // Get student profile
        $stmt = $this->db->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
        $stmt->execute([$service['student_id']]);
        $studentProfile = $stmt->fetch();

        // Get associated orders
        $ordersSql = "SELECT o.*, u.email as client_email, u.name as client_name
                      FROM orders o
                      LEFT JOIN users u ON o.client_id = u.id
                      WHERE o.service_id = :service_id
                      ORDER BY o.created_at DESC
                      LIMIT 20";
        $ordersStmt = $this->db->prepare($ordersSql);
        $ordersStmt->execute(['service_id' => $id]);
        $orders = $ordersStmt->fetchAll();

        // Calculate statistics
        $stats = [
            'total_orders'     => count($orders),
            'active_orders'    => count(array_filter($orders, fn($o) => in_array($o['status'], ['pending', 'in_progress', 'delivered', 'revision_requested']))),
            'completed_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'completed')),
            'cancelled_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'cancelled')),
        ];

        // Check if service can be deleted (no active orders)
        $canDelete = $stats['active_orders'] === 0;

        // Render view
        include __DIR__ . '/../../views/admin/services/show.php';
    }

    /**
     * Show service moderation interface
     *
     * GET /admin/services
     */
    public function services(): void
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
        $status     = $_GET['status'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        $page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage    = 20;
        $offset     = ($page - 1) * $perPage;

        // Build query
        $sql = "SELECT s.id, s.student_id, s.category_id, s.title, s.description, s.tags,
                       s.price, s.delivery_days, s.sample_files, s.status, s.created_at, s.updated_at,
                       c.name as category_name,
                       u.email as student_email, u.name as student_name,
                       sp.average_rating,
                       (SELECT COUNT(*) FROM orders WHERE service_id = s.id AND status IN ('pending', 'in_progress', 'delivered', 'revision_requested')) as active_orders_count
                FROM services s
                LEFT JOIN categories c ON s.category_id = c.id
                LEFT JOIN users u ON s.student_id = u.id
                LEFT JOIN student_profiles sp ON u.id = sp.user_id
                WHERE 1=1";

        $params = [];

        // Apply status filter - only filter if a specific status is selected
        // When status is empty/null, show all services regardless of status
        if (! empty($status) && in_array($status, ['inactive', 'active', 'paused'])) {
            $sql .= " AND s.status = :status";
            $params['status'] = $status;
        }

        // Apply category filter
        if ($categoryId) {
            $sql .= " AND s.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        // Get total count for pagination
        $countSql  = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];
        $totalPages = ceil($totalCount / $perPage);

        // Add ordering and pagination - show flagged services at top
        $sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        // Execute query
        $stmt = $this->db->prepare($sql);

        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset' || $key === 'category_id') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();
        $services = $stmt->fetchAll();

        // Decode JSON fields for each service
        foreach ($services as &$service) {
            $service['tags']         = $service['tags'] ? json_decode($service['tags'], true) : [];
            $service['sample_files'] = $service['sample_files'] ? json_decode($service['sample_files'], true) : [];
        }

        // Get all categories for filter dropdown
        $categories = $this->serviceRepository->getAllCategories();

        // Render view
        include __DIR__ . '/../../views/admin/services/index.php';
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

    /**
     * Show dispute management interface
     *
     * GET /admin/disputes
     */
    public function disputes(): void
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
        $status  = $_GET['status'] ?? null;
        $page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $perPage = 20;

        // Get disputes
        $result = $this->disputeRepository->getAll($status, $page, $perPage);

        // Extract data
        $disputes   = $result['disputes'];
        $totalCount = $result['total'];
        $totalPages = $result['total_pages'];

        // Render view
        include __DIR__ . '/../../views/admin/disputes/index.php';
    }

    /**
     * Show dispute detail view with resolution actions
     *
     * GET /admin/disputes/{id}
     */
    public function showDispute(int $id): void
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

        // Get dispute with full details
        $dispute = $this->disputeRepository->findById($id);

        if (! $dispute) {
            $_SESSION['error'] = 'Dispute not found';
            header('Location: /admin/disputes');
            exit;
        }

        // Get messages for the order
        $messagesSql = "SELECT m.*, u.email as sender_email, u.name as sender_name, u.role as sender_role
                        FROM messages m
                        INNER JOIN users u ON m.sender_id = u.id
                        WHERE m.order_id = :order_id
                        ORDER BY m.created_at ASC";
        $messagesStmt = $this->db->prepare($messagesSql);
        $messagesStmt->execute(['order_id' => $dispute['order_id']]);
        $messages = $messagesStmt->fetchAll();

        // Decode JSON fields for messages
        foreach ($messages as &$message) {
            $message['attachments'] = $message['attachments'] ? json_decode($message['attachments'], true) : [];
        }

        // Get payment information
        $paymentSql  = "SELECT * FROM payments WHERE order_id = :order_id";
        $paymentStmt = $this->db->prepare($paymentSql);
        $paymentStmt->execute(['order_id' => $dispute['order_id']]);
        $payment = $paymentStmt->fetch();

        // Render view
        include __DIR__ . '/../../views/admin/disputes/show.php';
    }

    /**
     * Resolve a dispute
     *
     * POST /admin/disputes/{id}/resolve
     */
    public function resolveDispute(int $id): void
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

        // Get form data
        $resolution      = $_POST['resolution'] ?? '';
        $resolutionNotes = $_POST['resolution_notes'] ?? '';
        $partialAmount   = isset($_POST['partial_amount']) && $_POST['partial_amount'] !== ''
            ? (float) $_POST['partial_amount']
            : null;

        // Initialize dispute service
        require_once __DIR__ . '/../Services/DisputeService.php';
        require_once __DIR__ . '/../Repositories/OrderRepository.php';

        $orderRepository = new OrderRepository($this->db);
        $disputeService  = new DisputeService(
            $this->disputeRepository,
            $orderRepository,
            $this->notificationService,
            $this->db
        );

        // Resolve dispute
        $result = $disputeService->resolveDispute(
            $id,
            $adminUser['id'],
            $resolution,
            $resolutionNotes,
            $partialAmount
        );

        if (! $result['success']) {
            $_SESSION['error']       = 'Failed to resolve dispute';
            $_SESSION['form_errors'] = $result['errors'];
            header('Location: /admin/disputes/' . $id);
            exit;
        }

        $_SESSION['success'] = 'Dispute resolved successfully. Both parties have been notified.';
        header('Location: /admin/disputes/' . $id);
        exit;
    }

    /**
     * Show platform settings interface
     *
     * GET /admin/settings
     */
    public function settings(): void
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

        // Get current settings
        $settingsSql  = "SELECT setting_key, setting_value FROM platform_settings";
        $settingsStmt = $this->db->prepare($settingsSql);
        $settingsStmt->execute();
        $settingsRows = $settingsStmt->fetchAll();

        // Convert to associative array
        $settings = [];
        foreach ($settingsRows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Set defaults if not found
        $commissionRate      = $settings['commission_rate'] ?? '15';
        $maxRevisions        = $settings['max_revisions'] ?? '3';
        $minOrderPrice       = $settings['min_order_price'] ?? '10';
        $maxOrderPrice       = $settings['max_order_price'] ?? '10000';
        $orderDeliveryBuffer = $settings['order_delivery_buffer'] ?? '24';
        $platformName        = $settings['platform_name'] ?? 'Student Skills Marketplace';
        $supportEmail        = $settings['support_email'] ?? 'support@studentskills.com';

        // Render view
        include __DIR__ . '/../../views/admin/settings.php';
    }

    /**
     * Update platform settings
     *
     * POST /admin/settings/update
     */
    public function updateSettings(): void
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

        // Get form data
        $commissionRate      = $_POST['commission_rate'] ?? '';
        $maxRevisions        = $_POST['max_revisions'] ?? '';
        $minOrderPrice       = $_POST['min_order_price'] ?? '';
        $maxOrderPrice       = $_POST['max_order_price'] ?? '';
        $orderDeliveryBuffer = $_POST['order_delivery_buffer'] ?? '';
        $platformName        = trim($_POST['platform_name'] ?? '');
        $supportEmail        = trim($_POST['support_email'] ?? '');

        // Validate inputs
        $errors = [];

        // Validate commission rate
        if (empty($commissionRate)) {
            $errors[] = 'Commission rate is required';
        } elseif (! is_numeric($commissionRate)) {
            $errors[] = 'Commission rate must be a number';
        } elseif ($commissionRate < 0 || $commissionRate > 100) {
            $errors[] = 'Commission rate must be between 0 and 100';
        }

        // Validate max revisions
        if (empty($maxRevisions)) {
            $errors[] = 'Maximum revisions is required';
        } elseif (! is_numeric($maxRevisions) || $maxRevisions != (int) $maxRevisions) {
            $errors[] = 'Maximum revisions must be an integer';
        } elseif ($maxRevisions < 1) {
            $errors[] = 'Maximum revisions must be greater than 0';
        }

        // Validate min order price
        if (empty($minOrderPrice)) {
            $errors[] = 'Minimum order price is required';
        } elseif (! is_numeric($minOrderPrice)) {
            $errors[] = 'Minimum order price must be a number';
        } elseif ($minOrderPrice < 0) {
            $errors[] = 'Minimum order price must be greater than or equal to 0';
        }

        // Validate max order price
        if (empty($maxOrderPrice)) {
            $errors[] = 'Maximum order price is required';
        } elseif (! is_numeric($maxOrderPrice)) {
            $errors[] = 'Maximum order price must be a number';
        } elseif ($maxOrderPrice <= $minOrderPrice) {
            $errors[] = 'Maximum order price must be greater than minimum order price';
        }

        // Validate order delivery buffer
        if (empty($orderDeliveryBuffer)) {
            $errors[] = 'Order delivery buffer is required';
        } elseif (! is_numeric($orderDeliveryBuffer) || $orderDeliveryBuffer != (int) $orderDeliveryBuffer) {
            $errors[] = 'Order delivery buffer must be an integer';
        } elseif ($orderDeliveryBuffer < 0) {
            $errors[] = 'Order delivery buffer must be greater than or equal to 0';
        }

        // Validate platform name
        if (empty($platformName)) {
            $errors[] = 'Platform name is required';
        } elseif (strlen($platformName) > 100) {
            $errors[] = 'Platform name must not exceed 100 characters';
        }

        // Validate support email
        if (empty($supportEmail)) {
            $errors[] = 'Support email is required';
        } elseif (! filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Support email must be a valid email address';
        }

        // If validation fails, redirect back with errors
        if (! empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: /admin/settings');
            exit;
        }

        // Get old values for audit log
        $oldValuesSql  = "SELECT setting_key, setting_value FROM platform_settings WHERE setting_key IN ('commission_rate', 'max_revisions', 'min_order_price', 'max_order_price', 'order_delivery_buffer', 'platform_name', 'support_email')";
        $oldValuesStmt = $this->db->prepare($oldValuesSql);
        $oldValuesStmt->execute();
        $oldValuesRows = $oldValuesStmt->fetchAll();

        $oldValues = [];
        foreach ($oldValuesRows as $row) {
            $oldValues[$row['setting_key']] = $row['setting_value'];
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Update or insert all settings
            $settingsToUpdate = [
                'commission_rate'       => $commissionRate,
                'max_revisions'         => $maxRevisions,
                'min_order_price'       => $minOrderPrice,
                'max_order_price'       => $maxOrderPrice,
                'order_delivery_buffer' => $orderDeliveryBuffer,
                'platform_name'         => $platformName,
                'support_email'         => $supportEmail,
            ];

            foreach ($settingsToUpdate as $key => $value) {
                $this->upsertSetting($key, $value, $adminUser['id']);

                // Log audit entry if value changed
                if (! isset($oldValues[$key]) || $oldValues[$key] != $value) {
                    $this->logAudit($adminUser['id'], 'setting_updated', 'platform_settings', 0, [
                        'setting_key' => $key,
                        'old_status'  => $oldValues[$key] ?? null,
                        'new_status'  => $value,
                    ]);
                }
            }

            // Commit transaction
            $this->db->commit();

            $_SESSION['success'] = 'Platform settings updated successfully';
            header('Location: /admin/settings');
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollBack();

            error_log('Failed to update settings: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to update settings. Please try again.';
            header('Location: /admin/settings');
            exit;
        }
    }

    /**
     * Upsert a platform setting
     */
    private function upsertSetting(string $key, string $value, int $updatedBy): void
    {
        $sql = "INSERT INTO platform_settings (setting_key, setting_value, updated_by, updated_at)
                VALUES (:key, :value, :updated_by, NOW())
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'key'        => $key,
            'value'      => $value,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Show category management interface
     *
     * GET /admin/categories
     */
    public function categories(): void
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

        // Get all categories with service count
        $categories = $this->categoryRepository->getAllWithServiceCount();

        // Render view
        include __DIR__ . '/../../views/admin/categories.php';
    }

    /**
     * Create a new category
     *
     * POST /admin/categories/create
     */
    public function createCategory(): void
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

        // Get form data
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate inputs
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Category name is required';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Category name must not exceed 100 characters';
        }

        // If validation fails, redirect back with errors
        if (! empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: /admin/categories');
            exit;
        }

        // Generate slug
        $slug = $this->categoryRepository->generateSlug($name);

        // Create category
        try {
            $categoryId = $this->categoryRepository->create([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
            ]);

            // Log audit entry
            $this->logAudit($adminUser['id'], 'category_created', 'category', $categoryId, [
                'old_status' => null,
                'new_status' => 'created',
            ]);

            $_SESSION['success'] = 'Category created successfully';
            header('Location: /admin/categories');
            exit;
        } catch (Exception $e) {
            error_log('Failed to create category: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to create category. Please try again.';
            header('Location: /admin/categories');
            exit;
        }
    }

    /**
     * Show edit category form
     *
     * GET /admin/categories/{id}/edit
     */
    public function editCategory(int $id): void
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

        // Get the category
        $category = $this->categoryRepository->findById($id);
        if (! $category) {
            $_SESSION['error'] = 'Category not found';
            header('Location: /admin/categories');
            exit;
        }

        // Render view
        include __DIR__ . '/../../views/admin/categories/edit.php';
    }

    /**
     * Update a category
     *
     * POST /admin/categories/{id}/update
     */
    public function updateCategory(int $id): void
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

        // Get the category
        $category = $this->categoryRepository->findById($id);
        if (! $category) {
            $_SESSION['error'] = 'Category not found';
            header('Location: /admin/categories');
            exit;
        }

        // Get form data
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate inputs
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Category name is required';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Category name must not exceed 100 characters';
        }

        // If validation fails, redirect back with errors
        if (! empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old']         = $_POST;
            header('Location: /admin/categories/' . $id . '/edit');
            exit;
        }

        // Generate slug (excluding current category ID to allow keeping same name)
        $slug = $this->categoryRepository->generateSlug($name, $id);

        // Update category
        try {
            $this->categoryRepository->update($id, [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
            ]);

            // Log audit entry
            $this->logAudit($adminUser['id'], 'category_updated', 'category', $id, [
                'old_status' => json_encode([
                    'name'        => $category['name'],
                    'description' => $category['description'],
                ]),
                'new_status' => json_encode([
                    'name'        => $name,
                    'description' => $description,
                ]),
            ]);

            $_SESSION['success'] = 'Category updated successfully';
            header('Location: /admin/categories');
            exit;
        } catch (Exception $e) {
            error_log('Failed to update category: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to update category. Please try again.';
            header('Location: /admin/categories/' . $id . '/edit');
            exit;
        }
    }

    /**
     * Delete a category
     *
     * POST /admin/categories/{id}/delete
     */
    public function deleteCategory(int $id): void
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

        // Get the category
        $category = $this->categoryRepository->findById($id);
        if (! $category) {
            $_SESSION['error'] = 'Category not found';
            header('Location: /admin/categories');
            exit;
        }

        // Check if category has services
        if ($this->categoryRepository->hasServices($id)) {
            $_SESSION['error'] = 'Cannot delete category with existing services. Please reassign or delete the services first.';
            header('Location: /admin/categories');
            exit;
        }

        // Delete the category
        try {
            $this->categoryRepository->delete($id);

            // Log audit entry
            $this->logAudit($adminUser['id'], 'category_deleted', 'category', $id, [
                'old_status' => 'active',
                'new_status' => 'deleted',
            ]);

            $_SESSION['success'] = 'Category deleted successfully';
            header('Location: /admin/categories');
            exit;
        } catch (Exception $e) {
            error_log('Failed to delete category: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete category. Please try again.';
            header('Location: /admin/categories');
            exit;
        }
    }

    /**
     * Recursively delete a directory and its contents
     */
    private function deleteDirectory(string $dir): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }
}
