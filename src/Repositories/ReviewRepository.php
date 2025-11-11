<?php

/**
 * Review Repository
 *
 * Handles database operations for reviews
 */
class ReviewRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new review
     *
     * @param array $data Review data
     * @return int The ID of the created review
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO reviews (
            order_id, client_id, student_id, rating, comment,
            can_edit_until, created_at, updated_at
        ) VALUES (
            :order_id, :client_id, :student_id, :rating, :comment,
            :can_edit_until, NOW(), NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id'       => $data['order_id'],
            'client_id'      => $data['client_id'],
            'student_id'     => $data['student_id'],
            'rating'         => $data['rating'],
            'comment'        => $data['comment'] ?? null,
            'can_edit_until' => $data['can_edit_until'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find review by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT r.*,
                       u_client.name as client_name, u_client.email as client_email,
                       u_student.name as student_name, u_student.email as student_email,
                       o.service_id, s.title as service_title
                FROM reviews r
                LEFT JOIN users u_client ON r.client_id = u_client.id
                LEFT JOIN users u_student ON r.student_id = u_student.id
                LEFT JOIN orders o ON r.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                WHERE r.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $review = $stmt->fetch();

        return $review ?: null;
    }

    /**
     * Find review by order ID
     *
     * @param int $orderId
     * @return array|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        $sql = "SELECT r.*,
                       u_client.name as client_name,
                       u_student.name as student_name
                FROM reviews r
                LEFT JOIN users u_client ON r.client_id = u_client.id
                LEFT JOIN users u_student ON r.student_id = u_student.id
                WHERE r.order_id = :order_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);

        $review = $stmt->fetch();

        return $review ?: null;
    }

    /**
     * Update a review
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['rating'])) {
            $fields[]         = 'rating = :rating';
            $params['rating'] = $data['rating'];
        }

        if (isset($data['comment'])) {
            $fields[]          = 'comment = :comment';
            $params['comment'] = $data['comment'];
        }

        if (isset($data['student_reply'])) {
            $fields[]                = 'student_reply = :student_reply';
            $params['student_reply'] = $data['student_reply'];
            $fields[]                = 'student_replied_at = NOW()';
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql  = "UPDATE reviews SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Get reviews for a student
     *
     * @param int $studentId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function findByStudentId(int $studentId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT r.*,
                       u_client.name as client_name,
                       o.service_id, s.title as service_title
                FROM reviews r
                LEFT JOIN users u_client ON r.client_id = u_client.id
                LEFT JOIN orders o ON r.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                WHERE r.student_id = :student_id
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get total count of reviews for a student
     *
     * @param int $studentId
     * @return int
     */
    public function countByStudentId(int $studentId): int
    {
        $sql = "SELECT COUNT(*) as count FROM reviews WHERE student_id = :student_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);

        $result = $stmt->fetch();

        return (int) $result['count'];
    }

    /**
     * Calculate average rating for a student
     *
     * @param int $studentId
     * @return float
     */
    public function calculateAverageRating(int $studentId): float
    {
        $sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE student_id = :student_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);

        $result = $stmt->fetch();

        return $result && $result['avg_rating'] ? round((float) $result['avg_rating'], 2) : 0.00;
    }

    /**
     * Update student profile with average rating and total reviews
     *
     * @param int $studentId
     * @return bool
     */
    public function updateStudentRating(int $studentId): bool
    {
        $avgRating    = $this->calculateAverageRating($studentId);
        $totalReviews = $this->countByStudentId($studentId);

        $sql = "UPDATE student_profiles
                SET average_rating = :average_rating,
                    total_reviews = :total_reviews,
                    updated_at = NOW()
                WHERE user_id = :student_id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'average_rating' => $avgRating,
            'total_reviews'  => $totalReviews,
            'student_id'     => $studentId,
        ]);
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
}
