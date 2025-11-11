<?php

require_once __DIR__ . '/../Services/SearchService.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';

/**
 * Discovery Controller
 *
 * Handles service discovery and search for clients
 */
class DiscoveryController
{
    private SearchService $searchService;

    public function __construct()
    {
        $db                  = require __DIR__ . '/../../config/database.php';
        $repository          = new ServiceRepository($db);
        $this->searchService = new SearchService($repository);
    }

    /**
     * Search services with filters
     */
    public function search(): void
    {
        // Get search parameters from query string
        $query       = $_GET['q'] ?? '';
        $categoryId  = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $minPrice    = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
        $maxPrice    = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
        $maxDelivery = isset($_GET['max_delivery']) && $_GET['max_delivery'] !== '' ? (int) $_GET['max_delivery'] : null;
        $minRating   = isset($_GET['min_rating']) && $_GET['min_rating'] !== '' ? (float) $_GET['min_rating'] : null;
        $sortBy      = $_GET['sort'] ?? 'relevance';
        $page        = isset($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;

        // Build filters array
        $filters = [
            'category_id'  => $categoryId,
            'min_price'    => $minPrice,
            'max_price'    => $maxPrice,
            'max_delivery' => $maxDelivery,
            'min_rating'   => $minRating,
        ];

        // Perform search
        $result = $this->searchService->search($query, $filters, $sortBy, $page);

        // Get all categories for filter sidebar
        $categories = $this->searchService->getAllCategories();

        // Render view
        view('client.services.index', [
            'services'         => $result['services'],
            'total'            => $result['total'],
            'page'             => $page,
            'totalPages'       => $result['total_pages'],
            'perPage'          => $result['per_page'],
            'query'            => $query,
            'categories'       => $categories,
            'selectedCategory' => $categoryId,
            'minPrice'         => $minPrice,
            'maxPrice'         => $maxPrice,
            'maxDelivery'      => $maxDelivery,
            'minRating'        => $minRating,
            'sortBy'           => $sortBy,
        ], 'base');
    }

    /**
     * Show service detail page
     */
    public function show($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->searchService->getServiceWithDetails($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Get reviews for this service's student
        $reviews = $this->searchService->getStudentReviews($service['student_id'], 1, 5);

        // Render view
        view('client.services.show', [
            'service' => $service,
            'reviews' => $reviews,
        ], 'base');
    }
}
