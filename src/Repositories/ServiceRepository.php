<?php

/**
 * Service Repository
 *
 * Handles database operations for services
 */
class ServiceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new service
     *
     * @param array $data Service data
     * @return int The ID of the created service
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO services (
            student_id, category_id, title, description, tags,
            price, delivery_days, sample_files, status, created_at, updated_at
        ) VALUES (
            :student_id, :category_id, :title, :description, :tags,
            :price, :delivery_days, :sample_files, :status, NOW(), NOW()
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            "student_id"    => $data["student_id"],
            "category_id"   => $data["category_id"],
            "title"         => $data["title"],
            "description"   => $data["description"],
            "tags"          => json_encode($data["tags"] ?? []),
            "price"         => $data["price"],
            "delivery_days" => $data["delivery_days"],
            "sample_files"  => json_encode($data["sample_files"] ?? []),
            "status"        => $data["status"] ?? "inactive",
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a service by ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT s.*, c.name as category_name
                FROM services s
                LEFT JOIN categories c ON s.category_id = c.id
                WHERE s.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["id" => $id]);

        $service = $stmt->fetch();

        if (! $service) {
            return null;
        }

        // Decode JSON fields (handle NULL values)
        $service["tags"] = $service["tags"]
            ? json_decode($service["tags"], true)
            : [];
        $service["sample_files"] = $service["sample_files"]
            ? json_decode($service["sample_files"], true)
            : [];

        // Ensure sample_files is always an array
        if (! is_array($service["sample_files"])) {
            $service["sample_files"] = [];
        }

        return $service;
    }

    /**
     * Update a service
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ["id" => $id];

        if (isset($data["category_id"])) {
            $fields[]              = "category_id = :category_id";
            $params["category_id"] = $data["category_id"];
        }

        if (isset($data["title"])) {
            $fields[]        = "title = :title";
            $params["title"] = $data["title"];
        }

        if (isset($data["description"])) {
            $fields[]              = "description = :description";
            $params["description"] = $data["description"];
        }

        if (isset($data["tags"])) {
            $fields[]       = "tags = :tags";
            $params["tags"] = json_encode($data["tags"]);
        }

        if (isset($data["price"])) {
            $fields[]        = "price = :price";
            $params["price"] = $data["price"];
        }

        if (isset($data["delivery_days"])) {
            $fields[]                = "delivery_days = :delivery_days";
            $params["delivery_days"] = $data["delivery_days"];
        }

        if (isset($data["sample_files"])) {
            $fields[]               = "sample_files = :sample_files";
            $params["sample_files"] = json_encode($data["sample_files"]);
        }

        if (isset($data["status"])) {
            $fields[]         = "status = :status";
            $params["status"] = $data["status"];
        }

        if (isset($data["last_modified_at"])) {
            $fields[]                   = "last_modified_at = :last_modified_at";
            $params["last_modified_at"] = $data["last_modified_at"];
        }

        if (isset($data["rejection_reason"])) {
            $fields[]                   = "rejection_reason = :rejection_reason";
            $params["rejection_reason"] = $data["rejection_reason"];
        }

        if (isset($data["rejected_at"])) {
            $fields[]                = "rejected_at = :rejected_at";
            $params["rejected_at"] = $data["rejected_at"];
        }

        if (isset($data["rejected_by"])) {
            $fields[]                = "rejected_by = :rejected_by";
            $params["rejected_by"] = $data["rejected_by"];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";

        $sql =
        "UPDATE services SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Delete a service
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql  = "DELETE FROM services WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(["id" => $id]);
    }

    /**
     * Get all services for a student
     *
     * @param int $studentId
     * @return array
     */
    public function findByStudentId(int $studentId): array
    {
        $sql = "SELECT s.*, c.name as category_name
                FROM services s
                LEFT JOIN categories c ON s.category_id = c.id
                WHERE s.student_id = :student_id
                ORDER BY s.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["student_id" => $studentId]);

        $services = $stmt->fetchAll();

        // Decode JSON fields for each service (handle NULL values)
        foreach ($services as &$service) {
            $service["tags"] = $service["tags"]
                ? json_decode($service["tags"], true)
                : [];
            $service["sample_files"] = $service["sample_files"]
                ? json_decode($service["sample_files"], true)
                : [];

            // Ensure sample_files is always an array
            if (! is_array($service["sample_files"])) {
                $service["sample_files"] = [];
            }
        }

        return $services;
    }

    /**
     * Check if service has active orders
     *
     * @param int $serviceId
     * @return bool
     */
    public function hasActiveOrders(int $serviceId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM orders
                WHERE service_id = :service_id
                AND status IN ('pending', 'in_progress', 'delivered', 'revision_requested')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["service_id" => $serviceId]);

        $result = $stmt->fetch();

        return $result["count"] > 0;
    }

    /**
     * Get all categories
     *
     * @return array
     */
    public function getAllCategories(): array
    {
        $sql  = "SELECT * FROM categories ORDER BY name ASC";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Search services with filters
     *
     * @param string $query Search query
     * @param array $filters Filters array
     * @param string $sortBy Sort option
     * @param int $limit Results per page
     * @param int $offset Offset for pagination
     * @return array
     */
    public function search(
        string $query,
        array $filters,
        string $sortBy,
        int $limit,
        int $offset,
    ): array {
        $params   = [];
        $where    = ["s.status = 'active'"];
        $hasQuery = ! empty($query);

        // Fulltext search on title and description + tags JSON fallback
        if ($hasQuery) {
            $where[] =
                "(MATCH(s.title, s.description) AGAINST (:query IN NATURAL LANGUAGE MODE)
                  OR JSON_SEARCH(s.tags, 'one', :tag_query) IS NOT NULL)";
            $params["query"]     = $query;
            $params["tag_query"] = '%' . $query . '%';
        }

        // Category filter
        if (! empty($filters["category_id"])) {
            $where[]               = "s.category_id = :category_id";
            $params["category_id"] = $filters["category_id"];
        }

        // Price range filter
        if (isset($filters["min_price"]) && $filters["min_price"] !== null) {
            $where[]             = "s.price >= :min_price";
            $params["min_price"] = $filters["min_price"];
        }
        if (isset($filters["max_price"]) && $filters["max_price"] !== null) {
            $where[]             = "s.price <= :max_price";
            $params["max_price"] = $filters["max_price"];
        }

        // Delivery time filter
        if (! empty($filters["max_delivery"])) {
            $where[]                = "s.delivery_days <= :max_delivery";
            $params["max_delivery"] = $filters["max_delivery"];
        }

        // Rating filter
        if (! empty($filters["min_rating"])) {
            $where[]              = "sp.average_rating >= :min_rating";
            $params["min_rating"] = $filters["min_rating"];
        }

        $whereClause = implode(" AND ", $where);

        // Build SELECT relevance part (based only on FULLTEXT)
        $relevanceSelect = "";
        if ($hasQuery) {
            $relevanceSelect =
                "MATCH(s.title, s.description) AGAINST (:query_relevance IN NATURAL LANGUAGE MODE) AS relevance,";
            $params["query_relevance"] = $query;
        }

        // Determine sort order
        $orderBy = $this->determineOrderBy($sortBy, $hasQuery);

        $sql = "SELECT
                    {$relevanceSelect}
                    s.*,
                    c.name as category_name,
                    u.email as student_email, u.name as student_name,
                    sp.average_rating, sp.total_reviews
                FROM services s
                LEFT JOIN categories c ON s.category_id = c.id
                LEFT JOIN users u ON s.student_id = u.id
                LEFT JOIN student_profiles sp ON u.id = sp.user_id
                WHERE {$whereClause}
                {$orderBy}
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $services = $stmt->fetchAll();

        // Decode JSON fields for each service (handle NULL values)
        foreach ($services as &$service) {
            $service["tags"] = $service["tags"]
                ? json_decode($service["tags"], true)
                : [];
            $service["sample_files"] = $service["sample_files"]
                ? json_decode($service["sample_files"], true)
                : [];

            // Ensure sample_files is always an array
            if (! is_array($service["sample_files"])) {
                $service["sample_files"] = [];
            }
        }

        return $services;
    }

    /**
     * Count search results
     *
     * @param string $query Search query
     * @param array $filters Filters array
     * @return int
     */
    public function countSearch(string $query, array $filters): int
    {
        $params = [];
        $where  = ["s.status = 'active'"];

        // Fulltext search on title and description + tags JSON fallback
        if (! empty($query)) {
            $where[] =
                "(MATCH(s.title, s.description) AGAINST (:query IN NATURAL LANGUAGE MODE)
                  OR JSON_SEARCH(s.tags, 'one', :tag_query) IS NOT NULL)";
            $params["query"]     = $query;
            $params["tag_query"] = '%' . $query . '%';
        }

        // Category filter
        if (! empty($filters["category_id"])) {
            $where[]               = "s.category_id = :category_id";
            $params["category_id"] = $filters["category_id"];
        }

        // Price range filter
        if (isset($filters["min_price"]) && $filters["min_price"] !== null) {
            $where[]             = "s.price >= :min_price";
            $params["min_price"] = $filters["min_price"];
        }

        if (isset($filters["max_price"]) && $filters["max_price"] !== null) {
            $where[]             = "s.price <= :max_price";
            $params["max_price"] = $filters["max_price"];
        }

        // Delivery time filter
        if (! empty($filters["max_delivery"])) {
            $where[]                = "s.delivery_days <= :max_delivery";
            $params["max_delivery"] = $filters["max_delivery"];
        }

        // Rating filter
        if (! empty($filters["min_rating"])) {
            $where[]              = "sp.average_rating >= :min_rating";
            $params["min_rating"] = $filters["min_rating"];
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT COUNT(*) as count
                FROM services s
                LEFT JOIN student_profiles sp ON s.student_id = sp.user_id
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);

        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        $result = $stmt->fetch();

        return (int) $result["count"];
    }

    /**
     * Determine ORDER BY clause
     */
    private function determineOrderBy(string $sortBy, bool $hasQuery): string
    {
        switch ($sortBy) {
            case "price_asc":
                return "ORDER BY s.price ASC";
            case "price_desc":
                return "ORDER BY s.price DESC";
            case "rating":
                return "ORDER BY sp.average_rating DESC, sp.total_reviews DESC";
            case "delivery":
                return "ORDER BY s.delivery_days ASC";
            case "relevance":
            default:
                if ($hasQuery) {
                    // We selected relevance as an alias when $hasQuery === true
                    return "ORDER BY relevance DESC";
                }
                return "ORDER BY s.created_at DESC";
        }
    }

    /**
     * Find service by ID with student details
     *
     * @param int $id
     * @return array|null
     */
    public function findByIdWithStudent(int $id): ?array
    {
        $sql = "SELECT s.*, c.name as category_name,
                       u.id as student_id, u.email as student_email, u.name as student_name,
                       sp.bio, sp.skills, sp.average_rating, sp.total_reviews, sp.total_orders
                FROM services s
                LEFT JOIN categories c ON s.category_id = c.id
                LEFT JOIN users u ON s.student_id = u.id
                LEFT JOIN student_profiles sp ON u.id = sp.user_id
                WHERE s.id = :id AND s.status = 'active'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["id" => $id]);

        $service = $stmt->fetch();

        if (! $service) {
            return null;
        }

        // Decode JSON fields (handle NULL values)
        $service["tags"] = $service["tags"]
            ? json_decode($service["tags"], true)
            : [];
        $service["sample_files"] = $service["sample_files"]
            ? json_decode($service["sample_files"], true)
            : [];
        $service["skills"] = $service["skills"]
            ? json_decode($service["skills"], true)
            : [];

        // Ensure sample_files is always an array
        if (! is_array($service["sample_files"])) {
            $service["sample_files"] = [];
        }

        return $service;
    }

    /**
     * Get reviews for a student
     *
     * @param int $studentId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getStudentReviews(
        int $studentId,
        int $limit,
        int $offset,
    ): array {
        $sql = "SELECT r.*, u.name as client_name, u.email as client_email, o.id as order_id, s.title as service_title
                FROM reviews r
                LEFT JOIN users u ON r.client_id = u.id
                LEFT JOIN orders o ON r.order_id = o.id
                LEFT JOIN services s ON o.service_id = s.id
                WHERE r.student_id = :student_id
                AND r.is_hidden = 0
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":student_id", $studentId, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get reviews for a specific service
     *
     * @param int $serviceId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getServiceReviews(
        int $serviceId,
        int $limit,
        int $offset,
    ): array {
        $sql = "SELECT r.*, u.name as client_name, u.email as client_email, o.id as order_id
                FROM reviews r
                LEFT JOIN users u ON r.client_id = u.id
                LEFT JOIN orders o ON r.order_id = o.id
                WHERE o.service_id = :service_id
                AND r.is_hidden = 0
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":service_id", $serviceId, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Begin database transaction
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit database transaction
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * Rollback database transaction
     */
    public function rollback(): void
    {
        $this->db->rollBack();
    }

    /**
     * Get database connection
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
}
