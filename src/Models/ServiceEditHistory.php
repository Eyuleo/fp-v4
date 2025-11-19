<?php

/**
 * Service Edit History Model
 *
 * Handles service edit audit trail operations
 */
class ServiceEditHistory
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log a service edit
     *
     * @param int $serviceId
     * @param int $userId
     * @param string $fieldChanged
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param bool $hasActiveOrders
     * @return bool
     */
    public function logEdit(
        int $serviceId,
        int $userId,
        string $fieldChanged,
        $oldValue,
        $newValue,
        bool $hasActiveOrders = false
    ): bool {
        $sql = "INSERT INTO service_edit_history (
            service_id, user_id, field_changed, old_value, new_value, 
            changed_at, has_active_orders
        ) VALUES (
            :service_id, :user_id, :field_changed, :old_value, :new_value,
            NOW(), :has_active_orders
        )";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'service_id' => $serviceId,
            'user_id' => $userId,
            'field_changed' => $fieldChanged,
            'old_value' => $this->serializeValue($oldValue),
            'new_value' => $this->serializeValue($newValue),
            'has_active_orders' => $hasActiveOrders ? 1 : 0
        ]);
    }

    /**
     * Get edit history for a service
     *
     * @param int $serviceId
     * @param int|null $limit
     * @return array
     */
    public function getServiceHistory(int $serviceId, ?int $limit = null): array
    {
        $sql = "SELECT seh.*, u.name as user_name, u.email as user_email
                FROM service_edit_history seh
                LEFT JOIN users u ON seh.user_id = u.id
                WHERE seh.service_id = :service_id
                ORDER BY seh.changed_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':service_id', $serviceId, PDO::PARAM_INT);

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get edit history for a user
     *
     * @param int $userId
     * @param int|null $limit
     * @return array
     */
    public function getUserHistory(int $userId, ?int $limit = null): array
    {
        $sql = "SELECT seh.*, s.title as service_title
                FROM service_edit_history seh
                LEFT JOIN services s ON seh.service_id = s.id
                WHERE seh.user_id = :user_id
                ORDER BY seh.changed_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Serialize value for storage
     *
     * @param mixed $value
     * @return string|null
     */
    private function serializeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
