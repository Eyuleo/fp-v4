<?php

/**
 * Notification Repository
 *
 * Handles all database operations for notifications
 */
class NotificationRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new notification
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at)
            VALUES (:user_id, :type, :title, :message, :link, :is_read, NOW())
        ');

        $stmt->execute([
            'user_id' => $data['user_id'],
            'type'    => $data['type'],
            'title'   => $data['title'],
            'message' => $data['message'],
            'link'    => $data['link'] ?? null,
            'is_read' => isset($data['is_read']) ? (int) $data['is_read'] : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all notifications for a user
     */
    public function findByUserId(int $userId, bool $unreadOnly = false): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';

        if ($unreadOnly) {
            $sql .= ' AND is_read = 0';
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = ? AND is_read = 0
        ');

        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return (int) $result['count'];
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE notifications
            SET is_read = 1
            WHERE id = ? AND user_id = ?
        ');

        return $stmt->execute([$notificationId, $userId]);
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ? AND is_read = 0
        ');

        return $stmt->execute([$userId]);
    }

    /**
     * Find notification by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE id = ?');
        $stmt->execute([$id]);

        $notification = $stmt->fetch();

        return $notification ?: null;
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOlderThan(int $days): int
    {
        $stmt = $this->db->prepare('
            DELETE FROM notifications
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ');

        $stmt->execute([$days]);

        return $stmt->rowCount();
    }
}
