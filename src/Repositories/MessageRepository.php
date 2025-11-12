<?php

/**
 * Message Repository
 *
 * Handles database operations for messages
 */
class MessageRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new message
     *
     * @param array $data Message data
     * @return int The ID of the created message
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO messages (
            order_id, sender_id, content, attachments, is_flagged, created_at
        ) VALUES (
            :order_id, :sender_id, :content, :attachments, :is_flagged, NOW()
        )";

        $isFlaggedRaw = $data['is_flagged'] ?? null;
        $isFlagged    = ($isFlaggedRaw === '' || $isFlaggedRaw === null) ? 0 : (int) $isFlaggedRaw;

        $attachments = $data['attachments'] ?? [];
        if (! is_array($attachments)) {
            $attachments = [$attachments];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':order_id', (int) $data['order_id'], PDO::PARAM_INT);
        $stmt->bindValue(':sender_id', (int) $data['sender_id'], PDO::PARAM_INT);
        $stmt->bindValue(':content', $data['content'], PDO::PARAM_STR);
        $stmt->bindValue(':attachments', json_encode($attachments), PDO::PARAM_STR);
        $stmt->bindValue(':is_flagged', $isFlagged, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all messages for an order
     *
     * @param int $orderId
     * @return array
     */
    public function findByOrderId(int $orderId): array
    {
        $sql = "SELECT m.*,
                       u.email as sender_email, u.name as sender_name, u.role as sender_role
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.order_id = :order_id
                ORDER BY m.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $messages = $stmt->fetchAll();

        // Decode JSON fields for each message
        foreach ($messages as &$message) {
            $message['attachments'] = $message['attachments'] ? json_decode($message['attachments'], true) : [];
        }

        return $messages;
    }

    /**
     * Mark messages as read for a user
     *
     * @param int $orderId
     * @param int $userId
     * @param string $userRole
     * @return bool
     */
    public function markAsRead(int $orderId, int $userId, string $userRole): bool
    {
        // Determine which column to update based on user role
        $column = $userRole === 'client' ? 'read_by_client' : 'read_by_student';

        $sql = "UPDATE messages
                SET {$column} = TRUE
                WHERE order_id = :order_id
                AND sender_id != :user_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'order_id' => $orderId,
            'user_id'  => $userId,
        ]);
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
        // Build query based on user role
        if ($userRole === 'client') {
            $sql = "SELECT COUNT(*) as count
                    FROM messages m
                    INNER JOIN orders o ON m.order_id = o.id
                    WHERE o.client_id = :user_id
                    AND m.sender_id != :user_id
                    AND m.read_by_client = FALSE";
        } else {
            // student
            $sql = "SELECT COUNT(*) as count
                    FROM messages m
                    INNER JOIN orders o ON m.order_id = o.id
                    WHERE o.student_id = :user_id
                    AND m.sender_id != :user_id
                    AND m.read_by_student = FALSE";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $result = $stmt->fetch();

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Get messages after a specific message ID (for polling)
     *
     * @param int $orderId
     * @param int $afterId
     * @return array
     */
    public function findByOrderIdAfter(int $orderId, int $afterId): array
    {
        $sql = "SELECT m.*,
                       u.email as sender_email, u.name as sender_name, u.role as sender_role
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.order_id = :order_id
                AND m.id > :after_id
                ORDER BY m.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id' => $orderId,
            'after_id' => $afterId,
        ]);

        $messages = $stmt->fetchAll();

        // Decode JSON fields for each message
        foreach ($messages as &$message) {
            $message['attachments'] = $message['attachments'] ? json_decode($message['attachments'], true) : [];
        }

        return $messages;
    }

    /**
     * Get database connection
     *
     * @return PDO
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
}
