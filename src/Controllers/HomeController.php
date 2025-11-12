<?php

require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/CategoryRepository.php';

/**
 * Home Controller
 *
 * Handles landing page and public views
 */
class HomeController
{
    private ServiceRepository $serviceRepository;
    private CategoryRepository $categoryRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db                 = require __DIR__ . '/../../config/database.php';
        $this->serviceRepository  = new ServiceRepository($this->db);
        $this->categoryRepository = new CategoryRepository($this->db);
    }

    /**
     * Show landing page
     */
    public function home(): void
    {
        try {
            // Get featured services (top-rated, active services)
            $featuredServices = $this->getFeaturedServices(6);

            // Get all categories
            $categories = $this->categoryRepository->getAll();

            // Render landing page
            view('home', [
                'featuredServices' => $featuredServices,
                'categories'       => $categories,
            ], 'base');
        } catch (Exception $e) {
            // Log error and show basic landing page
            error_log('Error loading landing page: ' . $e->getMessage());
            view('home', [
                'featuredServices' => [],
                'categories'       => [],
            ], 'base');
        }
    }

    /**
     * Get featured services
     */
    private function getFeaturedServices(int $limit = 6): array
    {
        $sql = "
            SELECT
                s.*,
                u.email as student_email,
                sp.average_rating,
                sp.total_reviews,
                c.name as category_name
            FROM services s
            INNER JOIN users u ON s.student_id = u.id
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            LEFT JOIN categories c ON s.category_id = c.id
            WHERE s.status = 'active'
            ORDER BY sp.average_rating DESC, sp.total_reviews DESC, s.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
