<?php

/**
 * Category Repository
 *
 * Handles database operations for categories
 */
class CategoryRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all categories
     */
    public function getAll(): array
    {
        $sql  = "SELECT * FROM categories ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get all categories with service count
     */
    public function getAllWithServiceCount(): array
    {
        $sql = "SELECT c.*,
                       COUNT(s.id) as service_count
                FROM categories c
                LEFT JOIN services s ON c.id = s.category_id
                GROUP BY c.id
                ORDER BY c.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Find category by ID
     */
    public function findById(int $id): ?array
    {
        $sql  = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $sql  = "SELECT * FROM categories WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Create a new category
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO categories (name, slug, description, created_at)
                VALUES (:name, :slug, :description, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        // Clear categories cache
        // TODO: Consider using dependency injection or event system for better decoupling
        if (class_exists('ServiceRepository')) {
            ServiceRepository::clearCategoriesCache();
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a category
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE categories
                SET name = :name,
                    slug = :slug,
                    description = :description
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            'id'          => $id,
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);

        // Clear categories cache
        if ($result && class_exists('ServiceRepository')) {
            ServiceRepository::clearCategoriesCache();
        }

        return $result;
    }

    /**
     * Delete a category
     */
    public function delete(int $id): bool
    {
        $sql  = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute(['id' => $id]);

        // Clear categories cache
        // TODO: Consider using dependency injection or event system for better decoupling
        if ($result && class_exists('ServiceRepository')) {
            ServiceRepository::clearCategoriesCache();
        }

        return $result;
    }

    /**
     * Check if category has services
     */
    public function hasServices(int $id): bool
    {
        $sql  = "SELECT COUNT(*) as count FROM services WHERE category_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();

        return $result['count'] > 0;
    }

    /**
     * Generate a unique slug from name
     */
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Check if slug exists (excluding current category if updating)
        $originalSlug = $slug;
        $counter      = 1;

        while (true) {
            $existingCategory = $this->findBySlug($slug);

            // If no category found with this slug, it's available
            if (! $existingCategory) {
                break;
            }

            // If updating and the slug belongs to the current category, it's fine
            if ($excludeId && $existingCategory['id'] == $excludeId) {
                break;
            }

            // Otherwise, try next variation
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
