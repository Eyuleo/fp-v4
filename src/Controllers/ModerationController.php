<?php

require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Repositories/ViolationRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Services/ViolationService.php';
require_once __DIR__ . '/../Services/NotificationService.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Moderation Controller
 *
 * Handles message moderation and violation management
 */
class ModerationController
{
    private PDO $db;
    private MessageRepository $messageRepository;
    private ViolationRepository $violationRepository;
    private UserRepository $userRepository;
    private OrderRepository $orderRepository;
    private ViolationService $violationService;

    public function __construct()
    {
        $this->db = getDatabaseConnection();
        $this->messageRepository = new MessageRepository($this->db);
        $this->violationRepository = new ViolationRepository($this->db);
        $this->userRepository = new UserRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);

        // Initialize ViolationService
        $mailService = new MailService();
        $notificationRepository = new NotificationRepository($this->db);
        $notificationService = new NotificationService($mailService, $notificationRepository);
        
        $this->violationService = new ViolationService(
            $this->violationRepository,
            $this->userRepository,
            $this->messageRepository,
            $notificationService,
            $this->db
        );
    }

    /**
     * Display message moderation dashboard
     *
     * GET /admin/moderation/messages
     */
    public function messagesDashboard(): void
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
        $flaggedOnly = isset($_GET['flagged']) && $_GET['flagged'] === '1';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $senderId = isset($_GET['sender_id']) ? (int)$_GET['sender_id'] : null;
        $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Build query
        $sql = "SELECT m.id, m.sender_id, m.order_id, m.content, m.attachments, 
                       m.is_flagged, m.read_by_client, m.read_by_student, m.created_at,
                       sender.email as sender_email, sender.name as sender_name,
                       recipient.email as recipient_email, recipient.name as recipient_name,
                       o.status as order_status,
                       s.title as service_title
                FROM messages m
                LEFT JOIN users sender ON m.sender_id = sender.id
                LEFT JOIN orders o ON m.order_id = o.id
                LEFT JOIN users recipient ON (
                    CASE 
                        WHEN sender.id = o.client_id THEN o.student_id
                        ELSE o.client_id
                    END = recipient.id
                )
                LEFT JOIN services s ON o.service_id = s.id
                WHERE 1=1";

        $params = [];

        // Apply flagged filter
        if ($flaggedOnly) {
            $sql .= " AND m.is_flagged = TRUE";
        }

        // Apply date range filter
        if ($dateFrom) {
            $sql .= " AND DATE(m.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(m.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        // Apply sender filter
        if ($senderId) {
            $sql .= " AND m.sender_id = :sender_id";
            $params['sender_id'] = $senderId;
        }

        // Apply order filter
        if ($orderId) {
            $sql .= " AND m.order_id = :order_id";
            $params['order_id'] = $orderId;
        }

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];
        $totalPages = ceil($totalCount / $perPage);

        // Add ordering and pagination
        $sql .= " ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;

        // Execute query
        $stmt = $this->db->prepare($sql);

        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset' || $key === 'sender_id' || $key === 'order_id') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();
        $messages = $stmt->fetchAll();

        // Get violation count for each sender
        foreach ($messages as &$message) {
            $message['sender_violation_count'] = $this->violationRepository->countByUserId($message['sender_id']);
            
            // Decode attachments JSON
            $message['attachments'] = $message['attachments'] ? json_decode($message['attachments'], true) : [];
        }

        // Get conversation thread if viewing a specific message
        $conversationThread = null;
        $viewingMessageId = isset($_GET['view_message']) ? (int)$_GET['view_message'] : null;
        
        if ($viewingMessageId) {
            // Find the message to get its order_id
            $messageSql = "SELECT order_id FROM messages WHERE id = :id";
            $messageStmt = $this->db->prepare($messageSql);
            $messageStmt->execute(['id' => $viewingMessageId]);
            $viewingMessage = $messageStmt->fetch();
            
            if ($viewingMessage) {
                $conversationThread = $this->messageRepository->findByOrderId($viewingMessage['order_id']);
            }
        }

        // Render view
        include __DIR__ . '/../../views/admin/moderation/messages.php';
    }

    /**
     * Show violation confirmation form
     *
     * GET /admin/moderation/violations/confirm
     */
    public function showConfirmViolationForm(): void
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

        // Get parameters
        $messageId = isset($_GET['message_id']) ? (int)$_GET['message_id'] : null;
        $senderId = isset($_GET['sender_id']) ? (int)$_GET['sender_id'] : null;

        if (!$messageId || !$senderId) {
            $_SESSION['error'] = 'Message ID and Sender ID are required';
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Get message details
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            $_SESSION['error'] = 'Message not found';
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Get sender details
        $sender = $this->userRepository->findById($senderId);
        if (!$sender) {
            $_SESSION['error'] = 'Sender not found';
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Get sender's violation history
        $violations = $this->violationService->getUserViolations($senderId);

        // Get suggested penalty
        $suggestedPenalty = $this->violationService->calculateSuggestedPenalty($senderId);

        // Render view
        include __DIR__ . '/../../views/admin/moderation/confirm-violation.php';
    }

    /**
     * Confirm a message as a violation
     *
     * POST /admin/moderation/violations/confirm
     */
    public function confirmViolation(): void
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
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Get form data
        $messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : null;
        $violationType = $_POST['violation_type'] ?? null;
        $severity = $_POST['severity'] ?? null;
        $penaltyType = $_POST['penalty_type'] ?? null;
        $suspensionDays = isset($_POST['suspension_days']) ? (int)$_POST['suspension_days'] : null;
        $adminNotes = $_POST['admin_notes'] ?? null;

        if (!$messageId) {
            $_SESSION['error'] = 'Message ID is required';
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Call ViolationService to confirm violation
        $result = $this->violationService->confirmViolation($messageId, $user['id'], [
            'violation_type' => $violationType,
            'severity' => $severity,
            'penalty_type' => $penaltyType,
            'suspension_days' => $suspensionDays,
            'admin_notes' => $adminNotes,
        ]);

        if ($result['success']) {
            $_SESSION['success'] = 'Violation confirmed and penalty applied successfully';
        } else {
            $_SESSION['error'] = 'Failed to confirm violation: ' . implode(', ', $result['errors']);
        }

        header('Location: /admin/moderation/messages');
        exit;
    }

    /**
     * Dismiss a flagged message
     *
     * POST /admin/moderation/violations/dismiss
     */
    public function dismissFlag(): void
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
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Get message ID
        $messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : null;

        if (!$messageId) {
            $_SESSION['error'] = 'Message ID is required';
            header('Location: /admin/moderation/messages');
            exit;
        }

        // Call ViolationService to dismiss flag
        $result = $this->violationService->dismissFlag($messageId, $user['id']);

        if ($result['success']) {
            $_SESSION['success'] = 'Flag dismissed successfully';
        } else {
            $_SESSION['error'] = 'Failed to dismiss flag: ' . implode(', ', $result['errors']);
        }

        header('Location: /admin/moderation/messages');
        exit;
    }

    /**
     * View user violation history
     *
     * GET /admin/users/{id}/violations
     */
    public function viewUserViolations(int $id): void
    {
        // Check authentication
        if (!Auth::check()) {
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
        if (!$user) {
            $_SESSION['error'] = 'User not found';
            header('Location: /admin/users');
            exit;
        }

        // Get user violations
        $violations = $this->violationService->getUserViolations($id);

        // Render view
        include __DIR__ . '/../../views/admin/users/violations.php';
    }
}
