<?php

require_once __DIR__ . '/../Repositories/ServiceRepository.php';

/**
 * Search Service
 *
 * Handles service search and discovery logic
 */
class SearchService
{
    private ServiceRepository $repository;
    private const PER_PAGE = 20;

    public function __construct(ServiceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Search services with filters and pagination
     *
     * @param string $query Search query
     * @param array $filters Filters (category_id, min_price, max_price, max_delivery, min_rating)
     * @param string $sortBy Sort option (relevance, price_asc, price_desc, rating, delivery)
     * @param int $page Page number
     * @return array Search results with pagination info
     */
    public function search(string $query, array $filters, string $sortBy, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        // Get services from repository
        $services = $this->repository->search($query, $filters, $sortBy, self::PER_PAGE, $offset);
        $total    = $this->repository->countSearch($query, $filters);

        return [
            'services'    => $services,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => self::PER_PAGE,
            'total_pages' => ceil($total / self::PER_PAGE),
        ];
    }

    /**
     * Get service with full details including student profile
     *
     * @param int $serviceId
     * @return array|null
     */
    public function getServiceWithDetails(int $serviceId): ?array
    {
        return $this->repository->findByIdWithStudent($serviceId);
    }

    /**
     * Get reviews for a student
     *
     * @param int $studentId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getStudentReviews(int $studentId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->repository->getStudentReviews($studentId, $perPage, $offset);
    }

    /**
     * Get reviews for a specific service
     *
     * @param int $serviceId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getServiceReviews(int $serviceId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        return $this->repository->getServiceReviews($serviceId, $perPage, $offset);
    }

    /**
     * Get all categories
     *
     * @return array
     */
    public function getAllCategories(): array
    {
        return $this->repository->getAllCategories();
    }
}
