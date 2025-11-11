<?php

/**
 * Dispute Repository
 *
 * Data access layer for disputes
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
     * @return int Dispute ID
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO disputes (
            order_id, opened_by, reason, status, created_at
        ) VALUES (
            :order_id, :opened_by, :reason, :status, NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id'  => $data['order_id'],
            'opened_by' => $data['opened_by'],
            'reason'    => $data['reason'],
            'status'    => $data['status'] ?? 'open',
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find dispute by ID
     *
     * @param int $id Dispute ID
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT d.id, d.order_id, d.opened_by, d.reason, d.status,
                       d.resolution, d.resolution_notes, d.resolved_by, d.resolved_at, d.created_at,
                       o.status as order_status, o.price as order_price,
                       o.requirements, o.requirement_files, o.delivery_message, o.delivery_files,
                       o.created_at as order_created_at, o.deadline,
                       s.title as service_title,
                       client.id as client_id, client.email as client_email, client.name as client_name,
                       student.id as student_id, student.email as student_email, student.name as student_name,
                       opener.email as opened_by_email, opener.name as opened_by_name,
                       resolver.email as resolved_by_email, resolver.name as resolved_by_name
                FROM disputes d
                INNER JOIN orders o ON d.order_id = o.id
                INNER JOIN services s ON o.service_id = s.id
                INNER JOIN users client ON o.client_id = client.id
                INNER JOIN users student ON o.student_id = student.id
                INNER JOIN users opener ON d.opened_by = opener.id
                LEFT JOIN users resolver ON d.resolved_by = resolver.id
                WHERE d.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $dispute = $stmt->fetch();

        if ($dispute) {
            // Decode JSON fields
            $dispute['requirement_files'] = $dispute['requirement_files'] ? json_decode($dispute['requirement_files'], true) : [];
            $dispute['delivery_files']    = $dispute['delivery_files'] ? json_decode($dispute['delivery_files'], true) : [];
        }

        return $dispute ?: null;
    }

    /**
     * Find dispute by order ID
     *
     * @param int $orderId Order ID
     * @return array|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        $sql = "SELECT * FROM disputes WHERE order_id = :order_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Get all disputes with filters
     *
     * @param string|null $status Filter by status
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array
     */
    public function getAll(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT d.id, d.order_id, d.opened_by, d.reason, d.status,
                       d.resolution, d.resolution_notes, d.resolved_by, d.resolved_at, d.created_at,
                       o.status as order_status, o.price as order_price,
                       s.title as service_title,
                       client.email as client_email, client.name as client_name,
                       student.email as student_email, student.name as student_name,
                       opener.email as opened_by_email, opener.name as opened_by_name
                FROM disputes d
                INNER JOIN orders o ON d.order_id = o.id
                INNER JOIN services s ON o.service_id = s.id
                INNER JOIN users client ON o.client_id = client.id
                INNER JOIN users student ON o.student_id = student.id
                INNER JOIN users opener ON d.opened_by = opener.id
                WHERE 1=1";

        $params = [];

        if ($status && in_array($status, ['open', 'resolved'])) {
            $sql .= " AND d.status = :status";
            $params['status'] = $status;
        }

        // Get total count
        $countSql  = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];

        // Add ordering and pagination
        $sql .= " ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        $stmt = $this->db->prepare($sql);

        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();
        $disputes = $stmt->fetchAll();

        return [
            'disputes'    => $disputes,
            'total'       => $totalCount,
            'total_pages' => ceil($totalCount / $perPage),
            'page'        => $page,
            'per_page'    => $perPage,
        ];
    }

    /**
     * Update dispute
     *
     * @param int $id Dispute ID
     * @param array $data Data to update
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            $fields[]     = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql = "UPDATE disputes SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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
