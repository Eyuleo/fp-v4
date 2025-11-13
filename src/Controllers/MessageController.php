<?php

require_once __DIR__ . '/../Services/MessageService.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Message Controller
 *
 * Handles message-related HTTP requests
 */
class MessageController
{
    private MessageService $messageService;
    private OrderRepository $orderRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db = getDatabaseConnection();

        $messageRepository     = new MessageRepository($this->db);
        $this->orderRepository = new OrderRepository($this->db);
        $this->messageService  = new MessageService($messageRepository, $this->orderRepository);
    }

    /**
     * Display all message conversations
     *
     * GET /messages
     */
    public function index(): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view messages';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get all conversations for the user
        $conversations = $this->messageService->getUserConversations($user['id'], $user['role']);

        // Render messages index view
        view('messages.index', compact('conversations'), 'dashboard');

    }

    /**
     * Send a message
     *
     * POST /messages/send
     */
    public function send(): void
    {
        // Check authentication
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user = Auth::user();

        // Validate CSRF token
        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        // Get form data
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $content = $_POST['content'] ?? '';

        // Handle file uploads
        $files = [];
        if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            // Restructure $_FILES array for multiple files
            $fileCount = count($_FILES['attachments']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name'     => $_FILES['attachments']['name'][$i],
                        'type'     => $_FILES['attachments']['type'][$i],
                        'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                        'error'    => $_FILES['attachments']['error'][$i],
                        'size'     => $_FILES['attachments']['size'][$i],
                    ];
                }
            }
        }

        // Send message
        $result = $this->messageService->sendMessage($user['id'], $orderId, $content, $files);

        if (! $result['success']) {
            $_SESSION['error'] = implode(', ', array_values($result['errors']));
            header('Location: /messages/thread/' . $orderId);
            exit;
        }

        $_SESSION['success'] = 'Message sent successfully';
        header('Location: /messages/thread/' . $orderId);
        exit;
    }

    /**
     * View message thread for an order
     *
     * GET /messages/thread/{orderId}
     */
    public function thread(int $orderId): void
    {
        // Check authentication
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view messages';
            header('Location: /login');
            exit;
        }

        $user = Auth::user();

        // Get order
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        // Check authorization (client or student can view)
        if ($order['client_id'] !== $user['id'] &&
            $order['student_id'] !== $user['id'] &&
            $user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        // Get all messages for the order
        $messages = $this->messageService->getOrderMessages($orderId);

        // Mark messages as read for current user
        $this->messageService->markMessagesAsRead($orderId, $user['id'], $user['role']);

        // Render message thread view
        view('messages.thread', compact('order', 'messages', 'user'), 'dashboard');

    }

    /**
     * Poll for new messages (AJAX endpoint)
     *
     * GET /messages/poll?order_id={id}&after={messageId}
     */
    public function poll(): void
    {
        // Check authentication
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user = Auth::user();

        // Get parameters
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $afterId = (int) ($_GET['after'] ?? 0);

        if (! $orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID required']);
            exit;
        }

        // Get order to verify authorization
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        // Check authorization
        if ($order['client_id'] !== $user['id'] &&
            $order['student_id'] !== $user['id'] &&
            $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Get new messages
        $newMessages = $this->messageService->getNewMessages($orderId, $afterId);

        // Mark new messages as read
        if (! empty($newMessages)) {
            $this->messageService->markMessagesAsRead($orderId, $user['id'], $user['role']);
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'messages' => $newMessages,
        ]);
        exit;
    }

    /**
     * Get unread message count (AJAX endpoint)
     *
     * GET /messages/unread-count
     */
    public function unreadCount(): void
    {
        // Check authentication
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user = Auth::user();

        // Get unread count
        $count = $this->messageService->getUnreadCount($user['id'], $user['role']);

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count'   => $count,
        ]);
        exit;
    }
}
