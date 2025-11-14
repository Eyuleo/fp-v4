<?php

require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/NotificationRepository.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/FileService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MailService.php';

/**
 * Message Service
 *
 * Business logic for messaging system
 */
class MessageService
{
    private MessageRepository $messageRepository;
    private OrderRepository $orderRepository;
    private UserRepository $userRepository;
    private ServiceRepository $serviceRepository;
    private EmailService $emailService;
    private FileService $fileService;
    private NotificationService $notificationService;
    private PDO $db;

    // Patterns that suggest off-platform communication
    private array $suspiciousPatterns = [
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email addresses
        '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',                       // Phone numbers (US format)
        '/\b\d{10,}\b/',                                         // Long number sequences
        '/\bwhatsapp\b/i',
        '/\btelegram\b/i',
        '/\bskype\b/i',
        '/\bpaypal\b/i',
        '/\bvenmo\b/i',
        '/\bcash\s*app\b/i',
        '/\bzelle\b/i',
        '/\bbank\s*transfer\b/i',
        '/\bwire\s*transfer\b/i',
        '/\boff\s*platform\b/i',
        '/\boutside\s*of\s*platform\b/i',
    ];

    public function __construct(MessageRepository $messageRepository, OrderRepository $orderRepository)
    {
        $this->messageRepository   = $messageRepository;
        $this->orderRepository     = $orderRepository;
        $this->emailService        = new EmailService();
        $this->fileService         = new FileService();
        $this->db                  = $orderRepository->getDb();
        $this->userRepository      = new UserRepository($this->db);
        $this->serviceRepository   = new ServiceRepository($this->db);
        $mailService               = new MailService();
        $notificationRepository    = new NotificationRepository($this->db);
        $this->notificationService = new NotificationService($mailService, $notificationRepository);
    }

    /**
     * Send a message
     *
     * @param int $senderId
     * @param int $orderId
     * @param string $content
     * @param array $attachments Uploaded files (can be raw $_FILES shape or already-normalized)
     * @return array ['success' => bool, 'message_id' => int|null, 'errors' => array]
     */
    public function sendMessage(int $senderId, int $orderId, string $content, array $attachments = []): array
    {
        // Normalize and validate content/attachments
        $content = trim($content ?? '');

        // Allow messages that have either text or attachments (or both)
        $hasAttachments = ! empty($attachments) &&
            (
            (isset($attachments['name']) && (! empty($attachments['name']) || (is_array($attachments['name']) && count(array_filter($attachments['name'])) > 0)))
            || (is_array($attachments) && ! isset($attachments['name']) && count($attachments) > 0)
        );

        if ($content === '' && ! $hasAttachments) {
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['content' => 'Message must include text or at least one attachment'],
            ];
        }

        // Get order to verify user is authorized
        $order = $this->orderRepository->findById($orderId);

        if (! $order) {
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['order' => 'Order not found'],
            ];
        }

        // Verify user is client or student of the order
        if ($order['client_id'] != $senderId && $order['student_id'] != $senderId) {
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['authorization' => 'You are not authorized to send messages in this order'],
            ];
        }

        // Check for suspicious content
        $isFlagged = $this->moderateMessage($content);

        // Handle file uploads
        $uploadedFiles = [];
        if ($hasAttachments) {
            $uploadResult = $this->handleFileUploads($orderId, $attachments);

            if (! $uploadResult['success']) {
                return [
                    'success'    => false,
                    'message_id' => null,
                    'errors'     => $uploadResult['errors'],
                ];
            }

            $uploadedFiles = $uploadResult['files'];
        }

        // Create message
        $messageData = [
            'order_id'    => $orderId,
            'sender_id'   => $senderId,
            'content'     => $content,
            'attachments' => $uploadedFiles,
            'is_flagged'  => $isFlagged,
        ];

        try {
            $messageId = $this->messageRepository->create($messageData);
        } catch (Exception $e) {
            error_log('Message create failed: ' . $e->getMessage());
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['database' => 'Failed to send message'],
            ];
        }

        if ($messageId <= 0) {
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['database' => 'Failed to send message'],
            ];
        }

        // Determine recipient
        $recipientId = $order['client_id'] == $senderId ? $order['student_id'] : $order['client_id'];

        // Send notification to recipient about new message
        try {
            $recipient = $this->userRepository->findById($recipientId);
            $sender    = $this->userRepository->findById($senderId);
            $service   = $this->serviceRepository->findById($order['service_id']);

            if ($recipient && $sender && $service) {
                // Create message array for notification
                $message = [
                    'id'          => $messageId,
                    'content'     => $content,
                    'attachments' => $uploadedFiles,
                    'sender_id'   => $senderId,
                    'order_id'    => $orderId,
                ];

                $this->notificationService->notifyMessageReceived($message, $recipient, $sender, $order, $service);
            }
        } catch (Exception $e) {
            error_log('Failed to send message received notification: ' . $e->getMessage());
        }

        return [
            'success'    => true,
            'message_id' => $messageId,
            'errors'     => [],
        ];
    }

    /**
     * Get all messages for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderMessages(int $orderId): array
    {
        return $this->messageRepository->findByOrderId($orderId);
    }

    /**
     * Mark messages as read for current user
     *
     * @param int $orderId
     * @param int $userId
     * @param string $userRole
     * @return bool
     */
    public function markMessagesAsRead(int $orderId, int $userId, string $userRole): bool
    {
        return $this->messageRepository->markAsRead($orderId, $userId, $userRole);
    }

    /**
     * Get unread message count for a user
     *
     * @param int $userId
     * @param string $userRole
     * @return int
     */
    public function getUnreadCount(int $userId, string $userRole): int
    {
        return $this->messageRepository->getUnreadCount($userId, $userRole);
    }

    public function getUserConversations(int $userId, string $userRole): array
    {
        // Build query based on user role
        if ($userRole === 'client') {
            $sql = "SELECT DISTINCT
                    o.id as order_id,
                    o.service_id,
                    s.title as service_title,
                    u.id as other_user_id,
                    u.name as other_user_name,
                    u.email as other_user_email,
                    (SELECT content FROM messages WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages m
                     WHERE m.order_id = o.id
                       AND m.sender_id != o.client_id
                       AND m.read_by_client = FALSE) as unread_count
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u ON o.student_id = u.id
                WHERE o.client_id = :user_id
                  AND EXISTS (SELECT 1 FROM messages WHERE order_id = o.id)
                ORDER BY last_message_time DESC";
        } else {
            // student
            $sql = "SELECT DISTINCT
                    o.id as order_id,
                    o.service_id,
                    s.title as service_title,
                    u.id as other_user_id,
                    u.name as other_user_name,
                    u.email as other_user_email,
                    (SELECT content FROM messages WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages m
                     WHERE m.order_id = o.id
                       AND m.sender_id != o.student_id
                       AND m.read_by_student = FALSE) as unread_count
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u ON o.client_id = u.id
                WHERE o.student_id = :user_id
                  AND EXISTS (SELECT 1 FROM messages WHERE order_id = o.id)
                ORDER BY last_message_time DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Get new messages after a specific message ID (for polling)
     *
     * @param int $orderId
     * @param int $afterId
     * @return array
     */
    public function getNewMessages(int $orderId, int $afterId): array
    {
        return $this->messageRepository->findByOrderIdAfter($orderId, $afterId);
    }

    /**
     * Check message content for suspicious patterns
     *
     * @param string $content
     * @return bool True if message should be flagged
     */
    private function moderateMessage(string $content): bool
    {
        foreach ($this->suspiciousPatterns as $pattern) {
            if ($content !== '' && preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle file uploads for message attachments
     *
     * @param int $orderId
     * @param array $files
     * @return array ['success' => bool, 'files' => array, 'errors' => array]
     */
    private function handleFileUploads(int $orderId, array $files): array
    {
        // Return success with empty files if no files provided
        if (empty($files)) {
            return [
                'success' => true,
                'files'   => [],
                'errors'  => [],
            ];
        }

        // Use FileService to upload multiple files
        $result = $this->fileService->uploadMultiple($files, 'messages', $orderId);

        return $result;
    }
}
