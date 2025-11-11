<?php

require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/../Repositories/OrderRepository.php';
require_once __DIR__ . '/EmailService.php';

/**
 * Message Service
 *
 * Business logic for messaging system
 */
class MessageService
{
    private MessageRepository $messageRepository;
    private OrderRepository $orderRepository;
    private EmailService $emailService;

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
        $this->messageRepository = $messageRepository;
        $this->orderRepository   = $orderRepository;
        $this->emailService      = new EmailService();
    }

    /**
     * Send a message
     *
     * @param int $senderId
     * @param int $orderId
     * @param string $content
     * @param array $attachments Uploaded files
     * @return array ['success' => bool, 'message_id' => int|null, 'errors' => array]
     */
    public function sendMessage(int $senderId, int $orderId, string $content, array $attachments = []): array
    {
        // Validate content
        $content = trim($content);
        if (empty($content)) {
            return [
                'success'    => false,
                'message_id' => null,
                'errors'     => ['content' => 'Message content is required'],
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
        if (! empty($attachments)) {
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

        $messageId = $this->messageRepository->create($messageData);

        // Determine recipient
        $recipientId = $order['client_id'] == $senderId ? $order['student_id'] : $order['client_id'];
        $recipient   = [
            'id'    => $recipientId,
            'email' => $order['client_id'] == $senderId ? $order['student_email'] : $order['client_email'],
            'name'  => $order['client_id'] == $senderId ? $order['student_name'] : $order['client_name'],
        ];

        // Send notification to recipient
        $this->emailService->sendMessageReceivedNotification($order, $recipient, $content);

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
            if (preg_match($pattern, $content)) {
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
        $uploadedFiles = [];
        $uploadDir     = __DIR__ . '/../../storage/uploads/messages/' . $orderId;

        // Create directory if it doesn't exist
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Allowed file extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
        $maxFileSize       = 10 * 1024 * 1024; // 10MB per file

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Validate file size
                if ($file['size'] > $maxFileSize) {
                    return [
                        'success' => false,
                        'files'   => [],
                        'errors'  => ['file_size' => 'File size must not exceed 10MB per file'],
                    ];
                }

                // Validate file extension
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (! in_array($extension, $allowedExtensions)) {
                    return [
                        'success' => false,
                        'files'   => [],
                        'errors'  => ['file_type' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions)],
                    ];
                }

                // Generate unique filename
                $filename    = uniqid() . '_' . time() . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $uploadedFiles[] = [
                        'filename'      => $filename,
                        'original_name' => $file['name'],
                        'path'          => 'storage/uploads/messages/' . $orderId . '/' . $filename,
                        'size'          => $file['size'],
                    ];
                }
            }
        }

        return [
            'success' => true,
            'files'   => $uploadedFiles,
            'errors'  => [],
        ];
    }
}
