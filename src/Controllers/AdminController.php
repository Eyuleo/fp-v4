<?php

require_once __DIR__ . '/../Repositories/PaymentRepository.php';
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

    public function __construct()
    {
        $this->db                = getDatabaseConnection();
        $this->paymentRepository = new PaymentRepository($this->db);
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
}
