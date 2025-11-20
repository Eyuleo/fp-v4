<?php

/**
 * Violation Repository
 *
 * Handles database operations for user violations
 */
class ViolationRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new violation record
     *
     * @param array $data Violation data
     * @return int The ID of the created violation
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO user_violations (
            user_id, message_id, violation_type, severity, penalty_type,
            suspension_days, admin_notes, confirmed_by, created_at
        ) VALUES (
            :user_id, :message_id, :violation_type, :severity, :penalty_type,
            :suspension_days, :admin_notes, :confirmed_by, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'         => $data['user_id'],
            'message_id'      => $data['message_id'] ?? null,
            'violation_type'  => $data['violation_type'],
            'severity'        => $data['severity'],
            'penalty_type'    => $data['penalty_type'],
            'suspension_days' => $data['suspension_days'] ?? null,
            'admin_notes'     => $data['admin_notes'] ?? null,
            'confirmed_by'    => $data['confirmed_by'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find all violations for a user
     *
     * @param int $userId
     * @return array
     */
    public function findByUserId(int $userId): array
    {
        $sql = "SELECT v.*,
                       u.email as admin_email, u.name as admin_name
                FROM user_violations v
                LEFT JOIN users u ON v.confirmed_by = u.id
                WHERE v.user_id = :user_id
                ORDER BY v.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count violations for a user
     *
     * @param int $userId
     * @return int
     */
    public function countByUserId(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count
                FROM user_violations
                WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Find violation by message ID
     *
     * @param int $messageId
     * @return array|null
     */
    public function findByMessageId(int $messageId): ?array
    {
        $sql = "SELECT v.*,
                       u.email as admin_email, u.name as admin_name
                FROM user_violations v
                LEFT JOIN users u ON v.confirmed_by = u.id
                WHERE v.message_id = :message_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['message_id' => $messageId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
