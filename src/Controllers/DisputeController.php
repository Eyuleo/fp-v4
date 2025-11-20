<?php

require_once __DIR__ . '/../Services/DisputeService.php';
require_once __DIR__ . '/../Repositories/DisputeRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Dispute Controller
 *
 * Handles dispute creation, viewing, and resolution
 */
class DisputeController
{
    private PDO $db;
    private DisputeService $disputeService;
    private DisputeRepository $disputeRepository;
    private OrderRepository $orderRepository;
    private MessageRepository $messageRepository;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->disputeService = new DisputeService($this->db);
        $this->disputeRepository = new DisputeRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);
        $this->messageRepository = new MessageRepository($this->db);
    }

    /**
     * Display disputes dashboard for admins (GET)
     *
     * GET /admin/disputes
     */
    public function index(): void
    {
        // Check authentication
        if (!Auth::check()) {
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
        $status = $_GET['status'] ?? 'open';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Get disputes by status
        if ($status === 'all') {
            // Get all disputes
            $sql = "SELECT d.*,
                           o.client_id, o.student_id, o.service_id, o.price as order_price,
                           o.status as order_status,
                           s.title as service_title,
                           u_opener.email as opener_email, u_opener.name as opener_name,
                           u_client.email as client_email, u_client.name as client_name,
                           u_student.email as student_email, u_student.name as student_name
                    FROM disputes d
                    LEFT JOIN orders o ON d.order_id = o.id
                    LEFT JOIN services s ON o.service_id = s.id
                    LEFT JOIN users u_opener ON d.opened_by = u_opener.id
                    LEFT JOIN users u_client ON o.client_id = u_client.id
                    LEFT JOIN users u_student ON o.student_id = u_student.id
                    ORDER BY d.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM disputes";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute();
            $totalCount = $countStmt->fetch()['total'];
        } else {
            // Get disputes by specific status
            $allDisputes = $this->disputeRepository->findByStatus($status);
            
            // Apply pagination
            $totalCount = count($allDisputes);
            $disputes = array_slice($allDisputes, $offset, $perPage);
        }

        $totalPages = ceil($totalCount / $perPage);

        // Render view
        include __DIR__ . '/../../views/admin/disputes/index.php';
    }

    /**
     * Show dispute creation form (GET)
     *
     * GET /disputes/create
     */
    public function showCreateForm(): void
    {
        // Check authentication
        if (!Auth::check()) {
            $_SESSION['error'] = 'Please login to create a dispute';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get order ID from query string
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

        if (!$orderId) {
            $_SESSION['error'] = 'Order ID is required';
            header('Location: /orders');
            exit;
        }

        // Get order details
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /orders');
            exit;
        }

        // Validate user is party to the order
        if ($order['client_id'] != $user['id'] && $order['student_id'] != $user['id']) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to create a dispute for this order';
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Check if can create dispute
        $canCreate = $this->disputeService->canUserCreateDispute($user['id'], $orderId);
        
        if (!$canCreate['can_create']) {
            $_SESSION['error'] = $canCreate['reason'];
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Render the dispute creation form
        include __DIR__ . '/../../views/disputes/create.php';
    }

    /**
     * Create a new dispute (POST)
     *
     * POST /disputes/create
     */
    public function create(): void
    {
        // Check authentication
        if (!Auth::check()) {
            $_SESSION['error'] = 'Please login to create a dispute';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /orders');
            exit;
        }

        // Get form data
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $reason = $_POST['reason'] ?? '';

        if (!$orderId) {
            $_SESSION['error'] = 'Order ID is required';
            header('Location: /orders');
            exit;
        }

        // Get order to redirect back to it
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Order not found';
            header('Location: /orders');
            exit;
        }

        // Validate user is party to the order
        if ($order['client_id'] != $user['id'] && $order['student_id'] != $user['id']) {
            http_response_code(403);
            $_SESSION['error'] = 'You are not authorized to create a dispute for this order';
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Validate required fields
        if (empty(trim($reason))) {
            $_SESSION['error'] = 'Dispute reason is required';
            header('Location: /orders/' . $orderId);
            exit;
        }

        // Call DisputeService to create dispute
        $result = $this->disputeService->createDispute($user['id'], $orderId, $reason);

        if ($result['success']) {
            $_SESSION['success'] = 'Dispute created successfully. An admin will review your case.';
        } else {
            $_SESSION['error'] = 'Failed to create dispute: ' . implode(', ', $result['errors']);
        }

        header('Location: /orders/' . $orderId);
        exit;
    }

    /**
     * Show dispute detail view (GET)
     *
     * GET /admin/disputes/{id}
     */
    public function show(int $id): void
    {
        // Check authentication
        if (!Auth::check()) {
            $_SESSION['error'] = 'Please login to access this page';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get dispute details
        $dispute = $this->disputeService->getDisputeById($id);

        if (!$dispute) {
            $_SESSION['error'] = 'Dispute not found';
            header('Location: /admin/disputes');
            exit;
        }

        // Check authorization - admin or party to the dispute
        $isAdmin = $user['role'] === 'admin';
        $isParty = ($user['id'] == $dispute['client_id'] || $user['id'] == $dispute['student_id']);

        if (!$isAdmin && !$isParty) {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Log admin access to audit_logs
        if ($isAdmin) {
            $sql = "INSERT INTO audit_logs (
                user_id, action, resource_type, resource_id,
                ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :resource_type, :resource_id,
                :ip_address, :user_agent, NOW()
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $user['id'],
                'action' => 'dispute.viewed',
                'resource_type' => 'dispute',
                'resource_id' => $id,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        }

        // Get complete message thread for the order
        $messages = $this->messageRepository->findByOrderId($dispute['order_id']);

        // Get all deliveries and revision requests
        $deliveryHistory = $this->orderRepository->getDeliveryHistory($dispute['order_id']);
        $revisionHistory = $this->orderRepository->getRevisionHistory($dispute['order_id']);

        // Get order details
        $order = $this->orderRepository->findById($dispute['order_id']);

        // Render view
        include __DIR__ . '/../../views/admin/disputes/show.php';
    }

    /**
     * Resolve a dispute (POST)
     *
     * POST /admin/disputes/{id}/resolve
     */
    public function resolve(int $id): void
    {
        // Check authentication
        if (!Auth::check()) {
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

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            $_SESSION['error'] = 'Invalid request';
            header('Location: /admin/disputes/' . $id);
            exit;
        }

        // Get form data
        $resolution = $_POST['resolution'] ?? null;
        $resolutionNotes = $_POST['resolution_notes'] ?? '';
        $refundPercentage = isset($_POST['refund_percentage']) ? $_POST['refund_percentage'] : null;
        $adminNotes = $_POST['admin_notes'] ?? '';

        // Validate resolution type
        $validResolutionTypes = ['release_to_student', 'refund_to_client', 'partial_refund'];
        
        if (!$resolution || !in_array($resolution, $validResolutionTypes)) {
            $_SESSION['error'] = 'Invalid resolution type selected';
            header('Location: /admin/disputes/' . $id);
            exit;
        }

        // Validate resolution notes
        if (empty(trim($resolutionNotes))) {
            $_SESSION['error'] = 'Resolution notes are required';
            header('Location: /admin/disputes/' . $id);
            exit;
        }

        // Validate refund percentage for partial refunds
        if ($resolution === 'partial_refund') {
            if ($refundPercentage === null || $refundPercentage === '') {
                $_SESSION['error'] = 'Refund percentage is required for partial refunds';
                header('Location: /admin/disputes/' . $id);
                exit;
            }

            $refundPercentage = (float) $refundPercentage;
            
            if ($refundPercentage < 0 || $refundPercentage > 100) {
                $_SESSION['error'] = 'Refund percentage must be between 0 and 100';
                header('Location: /admin/disputes/' . $id);
                exit;
            }
        }

        // Call DisputeService to process resolution
        $result = $this->disputeService->resolveDispute($id, $user['id'], [
            'resolution' => $resolution,
            'resolution_notes' => $resolutionNotes,
            'refund_percentage' => $refundPercentage,
            'admin_notes' => $adminNotes,
        ]);

        if ($result['success']) {
            $_SESSION['success'] = 'Dispute resolved successfully. All parties have been notified.';
            header('Location: /admin/disputes');
        } else {
            $_SESSION['error'] = 'Failed to resolve dispute: ' . implode(', ', $result['errors']);
            header('Location: /admin/disputes/' . $id);
        }

        exit;
    }
}

