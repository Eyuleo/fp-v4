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
     * @return array
     */
    public function findByClientId(int $clientId, ?string $status = null): array
    {
        $sql = "SELECT o.*,
                       s.title as service_title,
                       u_student.email as student_email, u_student.name as student_name
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $orders = $stmt->fetchAll();

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
    public function findByStudentId(int $studentId, ?string $status = null): array
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $orders = $stmt->fetchAll();

        // Decode JSON fields for each order (handle NULL values)
        foreach ($orders as &$order) {
            $order['requirement_files'] = $order['requirement_files'] ? json_decode($order['requirement_files'], true) : [];
            $order['delivery_files']    = $order['delivery_files'] ? json_decode($order['delivery_files'], true) : [];
        }

        return $orders;
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
        $sql = "UPDATE student_profiles
                SET available_balance = available_balance + :amount,
                    updated_at = NOW()
                WHERE user_id = :student_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'amount'     => $amount,
            'student_id' => $studentId,
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
        $sql = "UPDATE student_profiles
                SET total_orders = total_orders + 1,
                    updated_at = NOW()
                WHERE user_id = :student_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['student_id' => $studentId]);
    }
}
