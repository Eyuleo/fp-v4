<?php

/**
 * Order Repository
 *
 * Handles database operations for orders
 */
class OrderRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    /**
     * Create a new order
     *
     * @param array $data Order data
     * @return int The ID of the created order
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO orders (
            client_id, student_id, service_id, status, requirements,
            requirement_files, price, commission_rate, deadline,
            max_revisions, created_at, updated_at
        ) VALUES (
            :client_id, :student_id, :service_id, :status, :requirements,
            :requirement_files, :price, :commission_rate, :deadline,
            :max_revisions, NOW(), NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'client_id'         => $data['client_id'],
            'student_id'        => $data['student_id'],
            'service_id'        => $data['service_id'],
            'status'            => $data['status'],
            'requirements'      => $data['requirements'],
            'requirement_files' => json_encode($data['requirement_files'] ?? []),
            'price'             => $data['price'],
            'commission_rate'   => $data['commission_rate'],
            'deadline'          => $data['deadline'],
            'max_revisions'     => $data['max_revisions'] ?? 3,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find order by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT o.*,
                       s.title as service_title, s.delivery_days,
                       u_client.email as client_email, u_client.name as client_name,
                       u_student.email as student_email, u_student.name as student_name
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u_client ON o.client_id = u_client.id
                LEFT JOIN users u_student ON o.student_id = u_student.id
                WHERE o.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $order = $stmt->fetch();

        if (! $order) {
            return null;
        }

        // Decode JSON fields (handle NULL)
        $order['requirement_files'] = $order['requirement_files'] ? json_decode($order['requirement_files'], true) : [];
        $order['delivery_files']    = $order['delivery_files'] ? json_decode($order['delivery_files'], true) : [];

        return $order;
    }

    /**
     * Update an order
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['status'])) {
            $fields[]         = 'status = :status';
            $params['status'] = $data['status'];
        }

        if (isset($data['requirements'])) {
            $fields[]               = 'requirements = :requirements';
            $params['requirements'] = $data['requirements'];
        }

        if (isset($data['requirement_files'])) {
            $fields[]                    = 'requirement_files = :requirement_files';
            $params['requirement_files'] = json_encode($data['requirement_files']);
        }

        if (isset($data['delivery_message'])) {
            $fields[]                   = 'delivery_message = :delivery_message';
            $params['delivery_message'] = $data['delivery_message'];
        }

        if (isset($data['delivery_files'])) {
            $fields[]                 = 'delivery_files = :delivery_files';
            $params['delivery_files'] = json_encode($data['delivery_files']);
        }

        if (isset($data['revision_count'])) {
            $fields[]                 = 'revision_count = :revision_count';
            $params['revision_count'] = $data['revision_count'];
        }

        if (isset($data['completed_at'])) {
            $fields[]               = 'completed_at = :completed_at';
            $params['completed_at'] = $data['completed_at'];
        }

        if (isset($data['cancelled_at'])) {
            $fields[]               = 'cancelled_at = :cancelled_at';
            $params['cancelled_at'] = $data['cancelled_at'];
        }

        if (isset($data['cancellation_reason'])) {
            $fields[]                      = 'cancellation_reason = :cancellation_reason';
            $params['cancellation_reason'] = $data['cancellation_reason'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql  = "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Get orders for a client
     *
     * @param int $clientId
     * @param string|null $status Filter by status
     * @param int|null $limit Limit number of results
     * @return array
     */
    public function findByClientId(int $clientId, ?string $status = null, ?int $limit = null): array
    {
        $sql = "SELECT o.*,
                       COALESCE(s.title, 'Service Unavailable') as service_title,
                       s.delivery_days,
                       COALESCE(u_student.email, 'N/A') as student_email,
                       COALESCE(u_student.name, u_student.email, 'Unknown Student') as student_name
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u_student ON o.student_id = u_student.id
                WHERE o.client_id = :client_id";

        $params = ['client_id' => $clientId];

        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);

        // Bind limit parameter separately with correct type
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        // Bind other parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();

        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode JSON fields for each order (handle NULL values)
        foreach ($orders as &$order) {
            $order['requirement_files'] = $order['requirement_files'] ? json_decode($order['requirement_files'], true) : [];
            $order['delivery_files']    = $order['delivery_files'] ? json_decode($order['delivery_files'], true) : [];
        }

        return $orders;
    }

    /**
     * Get orders for a student
     *
     * @param int $studentId
     * @param string|null $status Filter by status
     * @return array
     */
    public function findByStudentId(int $studentId, ?string $status = null, ?int $limit = null): array
    {
        $sql = "SELECT o.*,
                       s.title as service_title,
                       u_client.email as client_email, u_client.name as client_name
                FROM orders o
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u_client ON o.client_id = u_client.id
                WHERE o.student_id = :student_id";

        $params = ['student_id' => $studentId];

        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params['limit'] = $limit;
        }

        $stmt = $this->db->prepare($sql);
        
        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        
        $stmt->execute();

        $orders = $stmt->fetchAll();

        // Decode JSON fields for each order (handle NULL values)
        foreach ($orders as &$order) {
            $order['requirement_files'] = $order['requirement_files'] ? json_decode($order['requirement_files'], true) : [];
            $order['delivery_files']    = $order['delivery_files'] ? json_decode($order['delivery_files'], true) : [];
        }

        return $orders;
    }

    public function getDefaultReviewWindowHours(): int
    {
        // Could be an env var; fallback to 24
        $hours = getenv('ORDER_REVIEW_WINDOW_HOURS');
        return ($hours && (int) $hours > 0) ? (int) $hours : 24;
    }

/**
 * Find delivered orders whose review window expired (not yet completed).
 */
    public function findExpiredReviewWindowOrders(): array
    {
        $stmt = $this->db->prepare("
        SELECT * FROM orders
        WHERE status = 'delivered'
          AND review_deadline IS NOT NULL
          AND review_deadline < NOW()
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

/**
 * Backfill review window for an already delivered order missing fields.
 */
    public function backfillReviewWindow(array $order): ?array
    {
        if ($order['status'] !== 'delivered') {
            return $order;
        }
        if (! empty($order['review_deadline'])) {
            return $order; // Already set
        }
        $deliveredAt = $order['delivered_at'] ?? $order['updated_at'] ?? $order['created_at'];
        if (! $deliveredAt) {
            $deliveredAt = date('Y-m-d H:i:s');
        }
        $hours          = $this->getDefaultReviewWindowHours();
        $reviewDeadline = date('Y-m-d H:i:s', strtotime($deliveredAt) + ($hours * 3600));

        $this->update((int) $order['id'], [
            'delivered_at'        => $deliveredAt,
            'review_window_hours' => $hours,
            'review_deadline'     => $reviewDeadline,
        ]);

        return $this->findById((int) $order['id']);
    }

    /**
     * Get platform commission rate setting
     *
     * @return float
     */
    public function getCommissionRate(): float
    {
        $sql    = "SELECT setting_value FROM platform_settings WHERE setting_key = 'commission_rate'";
        $stmt   = $this->db->query($sql);
        $result = $stmt->fetch();

        return $result ? (float) $result['setting_value'] : 15.0; // Default 15%
    }

    /**
     * Get max revisions setting
     *
     * @return int
     */
    public function getMaxRevisions(): int
    {
        $sql    = "SELECT setting_value FROM platform_settings WHERE setting_key = 'max_revisions'";
        $stmt   = $this->db->query($sql);
        $result = $stmt->fetch();

        return $result ? (int) $result['setting_value'] : 3; // Default 3
    }

    /**
     * Get revision history for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getRevisionHistory(int $orderId): array
    {
        $sql = "SELECT rh.*, u.name as requester_name, u.email as requester_email
                FROM order_revision_history rh
                LEFT JOIN users u ON rh.requested_by = u.id
                WHERE rh.order_id = :order_id
                ORDER BY rh.requested_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get current revision for an order
     *
     * @param int $orderId
     * @return array|null
     */
    public function getCurrentRevision(int $orderId): ?array
    {
        $sql = "SELECT rh.*, u.name as requester_name, u.email as requester_email
                FROM order_revision_history rh
                LEFT JOIN users u ON rh.requested_by = u.id
                WHERE rh.order_id = :order_id AND rh.is_current = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get delivery history for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getDeliveryHistory(int $orderId): array
    {
        $sql = "SELECT *
                FROM order_delivery_history
                WHERE order_id = :order_id
                ORDER BY delivered_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON fields for each delivery
        foreach ($deliveries as &$delivery) {
            $delivery['delivery_files'] = $delivery['delivery_files'] ? json_decode($delivery['delivery_files'], true) : [];
        }

        return $deliveries;
    }

    /**
     * Get current delivery for an order
     *
     * @param int $orderId
     * @return array|null
     */
    public function getCurrentDelivery(int $orderId): ?array
    {
        $sql = "SELECT *
                FROM order_delivery_history
                WHERE order_id = :order_id AND is_current = 1
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['delivery_files'] = $result['delivery_files'] ? json_decode($result['delivery_files'], true) : [];
        }
        return $result ?: null;
    }

    /**
     * Create delivery history entry
     *
     * @param int $orderId
     * @param string $deliveryMessage
     * @param array $deliveryFiles
     * @param int $deliveryNumber
     * @param bool $isCurrent
     * @return int The ID of the created delivery history entry
     */
    public function createDeliveryHistory(int $orderId, string $deliveryMessage, array $deliveryFiles, int $deliveryNumber, bool $isCurrent = true): int
    {
        $sql = "INSERT INTO order_delivery_history (
            order_id, delivery_message, delivery_files, delivery_number, is_current
        ) VALUES (
            :order_id, :delivery_message, :delivery_files, :delivery_number, :is_current
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id'         => $orderId,
            'delivery_message' => $deliveryMessage,
            'delivery_files'   => json_encode($deliveryFiles),
            'delivery_number'  => $deliveryNumber,
            'is_current'       => $isCurrent ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Mark all deliveries as not current for an order
     *
     * @param int $orderId
     * @return bool
     */
    public function markAllDeliveriesNotCurrent(int $orderId): bool
    {
        $sql = "UPDATE order_delivery_history SET is_current = 0 WHERE order_id = :order_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['order_id' => $orderId]);
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

    /**
     * Begin database transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit database transaction
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback database transaction
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Add amount to student's available balance
     *
     * @param int $studentId
     * @param float $amount
     * @return bool
     */
    public function addToStudentBalance(int $studentId, float $amount): bool
    {
        // Use UPSERT with distinct parameter names to avoid PDO errors
        $sql = "INSERT INTO student_profiles (
                    user_id, available_balance, skills, portfolio_files, created_at, updated_at
                ) VALUES (
                    :student_id, :amount_insert, '[]', '[]', NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE
                    available_balance = available_balance + :amount_update,
                    updated_at = NOW()";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'student_id'    => $studentId,
            'amount_insert' => $amount,
            'amount_update' => $amount,
        ]);
    }

    /**
     * Increment student's total orders counter
     *
     * @param int $studentId
     * @return bool
     */
    public function incrementStudentOrderCount(int $studentId): bool
    {
        // Ensure student profile exists
        $checkSql = "SELECT id, total_orders FROM student_profiles WHERE user_id = :student_id";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute(['student_id' => $studentId]);
        $profile = $checkStmt->fetch();
        
        if (!$profile) {
            // Create profile if it doesn't exist
            $createSql = "INSERT INTO student_profiles (user_id, total_orders, created_at, updated_at)
                          VALUES (:student_id, 1, NOW(), NOW())";
            $createStmt = $this->db->prepare($createSql);
            return $createStmt->execute(['student_id' => $studentId]);
        }
        $sql = "UPDATE student_profiles
                SET total_orders = total_orders + 1,
                    updated_at = NOW()
                WHERE user_id = :student_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['student_id' => $studentId]);
    }
}