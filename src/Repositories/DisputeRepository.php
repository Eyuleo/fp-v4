<?php

/**
 * Dispute Repository
 *
 * Handles database operations for disputes
 */
class DisputeRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new dispute
     *
     * @param array $data Dispute data
     * @return int The ID of the created dispute
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO disputes (
            order_id, opened_by, reason, status, refund_percentage, admin_notes, created_at
        ) VALUES (
            :order_id, :opened_by, :reason, :status, :refund_percentage, :admin_notes, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id'          => $data['order_id'],
            'opened_by'         => $data['opened_by'],
            'reason'            => $data['reason'],
            'status'            => $data['status'] ?? 'open',
            'refund_percentage' => $data['refund_percentage'] ?? null,
            'admin_notes'       => $data['admin_notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find dispute by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT d.*,
                       o.client_id, o.student_id, o.service_id, o.price as order_price,
                       o.status as order_status, o.requirements, o.delivery_message,
                       s.title as service_title,
                       u_opener.email as opener_email, u_opener.name as opener_name,
                       u_client.email as client_email, u_client.name as client_name,
                       u_student.email as student_email, u_student.name as student_name,
                       u_resolver.email as resolver_email, u_resolver.name as resolver_name
                FROM disputes d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u_opener ON d.opened_by = u_opener.id
                LEFT JOIN users u_client ON o.client_id = u_client.id
                LEFT JOIN users u_student ON o.student_id = u_student.id
                LEFT JOIN users u_resolver ON d.resolved_by = u_resolver.id
                WHERE d.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find dispute by order ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        $sql = "SELECT d.*,
                       o.client_id, o.student_id, o.service_id, o.price as order_price,
                       o.status as order_status,
                       u_opener.email as opener_email, u_opener.name as opener_name
                FROM disputes d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN users u_opener ON d.opened_by = u_opener.id
                WHERE d.order_id = :order_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Find disputes by status
     *
     * @param string $status
     * @return array
     */
    public function findByStatus(string $status): array
    {
        $sql = "SELECT d.*,
                       o.client_id, o.student_id, o.service_id, o.price as order_price,
                       o.status as order_status,
                       s.title as service_title,
                       u_opener.email as opener_email, u_opener.name as opener_name,
                       u_client.email as client_email, u_client.name as client_name,
                       u_student.email as student_email, u_student.name as student_name
                FROM disputes d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                LEFT JOIN users u_opener ON d.opened_by = u_opener.id
                LEFT JOIN users u_client ON o.client_id = u_client.id
                LEFT JOIN users u_student ON o.student_id = u_student.id
                WHERE d.status = :status
                ORDER BY d.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if an order has an open dispute
     *
     * @param int $orderId
     * @return bool
     */
    public function hasOpenDispute(int $orderId): bool
    {
        $sql = "SELECT COUNT(*) as count
                FROM disputes
                WHERE order_id = :order_id
                AND status = 'open'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['count'] ?? 0) > 0;
    }

    /**
     * Update a dispute
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

        if (isset($data['resolution'])) {
            $fields[]             = 'resolution = :resolution';
            $params['resolution'] = $data['resolution'];
        }

        if (isset($data['resolution_notes'])) {
            $fields[]                   = 'resolution_notes = :resolution_notes';
            $params['resolution_notes'] = $data['resolution_notes'];
        }

        if (isset($data['refund_percentage'])) {
            $fields[]                   = 'refund_percentage = :refund_percentage';
            $params['refund_percentage'] = $data['refund_percentage'];
        }

        if (isset($data['admin_notes'])) {
            $fields[]              = 'admin_notes = :admin_notes';
            $params['admin_notes'] = $data['admin_notes'];
        }

        if (isset($data['resolved_by'])) {
            $fields[]              = 'resolved_by = :resolved_by';
            $params['resolved_by'] = $data['resolved_by'];
        }

        if (isset($data['resolved_at'])) {
            $fields[]              = 'resolved_at = :resolved_at';
            $params['resolved_at'] = $data['resolved_at'];
        }

        if (empty($fields)) {
            return false;
        }

        $sql  = "UPDATE disputes SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }
}
