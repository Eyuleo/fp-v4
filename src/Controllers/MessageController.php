<?php
require_once __DIR__ . '/../Services/MessageService.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/FileService.php'; // Added for signed URLs

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

    public function index(): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view messages';
            header('Location: /login');
            exit;
        }

        $user          = Auth::user();
        $conversations = $this->messageService->getUserConversations($user['id'], $user['role']);
        view('messages.index', compact('conversations'), 'dashboard');
    }

    public function send(): void
    {
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user = Auth::user();

        if (! isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $content = $_POST['content'] ?? '';

        $files = [];
        if (isset($_FILES['attachments'])) {
            // pass raw $_FILES to FileService (it handles multi-file shape)
            $files = $_FILES['attachments'];
        }

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

    public function thread(int $orderId): void
    {
        if (! Auth::check()) {
            $_SESSION['error'] = 'Please login to view messages';
            header('Location: /login');
            exit;
        }

        $user  = Auth::user();
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            http_response_code(404);
            include __DIR__ . '/../../views/errors/404.php';
            exit;
        }

        if ($order['client_id'] !== $user['id']
            && $order['student_id'] !== $user['id']
            && $user['role'] !== 'admin') {
            http_response_code(403);
            include __DIR__ . '/../../views/errors/403.php';
            exit;
        }

        $messages    = $this->messageService->getOrderMessages($orderId);
        $fileService = new FileService();
        // attach signed URLs for existing attachments
        foreach ($messages as &$m) {
            if (! empty($m['attachments']) && is_array($m['attachments'])) {
                foreach ($m['attachments'] as &$a) {
                    if (! empty($a['path'])) {
                        $a['signed_url'] = $fileService->generateSignedUrl($a['path'], 1800);
                    }
                }
                unset($a);
            }
        }
        unset($m);

        $this->messageService->markMessagesAsRead($orderId, $user['id'], $user['role']);

        view('messages.thread', [
            'order'    => $order,
            'messages' => $messages,
            'user'     => $user,
        ], 'dashboard');
    }

    public function poll(): void
    {
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user    = Auth::user();
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $afterId = (int) ($_GET['after'] ?? 0);

        if (! $orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Order ID required']);
            exit;
        }

        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }

        if ($order['client_id'] !== $user['id']
            && $order['student_id'] !== $user['id']
            && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $newMessages = $this->messageService->getNewMessages($orderId, $afterId);

        if (! empty($newMessages)) {
            $this->messageService->markMessagesAsRead($orderId, $user['id'], $user['role']);
            $fileService = new FileService();
            foreach ($newMessages as &$m) {
                if (! empty($m['attachments']) && is_array($m['attachments'])) {
                    foreach ($m['attachments'] as &$a) {
                        if (! empty($a['path'])) {
                            $a['signed_url'] = $fileService->generateSignedUrl($a['path'], 1800);
                        }
                    }
                    unset($a);
                }
            }
            unset($m);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'messages' => $newMessages,
        ]);
        exit;
    }

    public function unreadCount(): void
    {
        if (! Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user  = Auth::user();
        $count = $this->messageService->getUnreadCount($user['id'], $user['role']);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count'   => $count,
        ]);
        exit;
    }
}
