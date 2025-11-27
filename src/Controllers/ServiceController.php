<?php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Services/ServiceService.php';
require_once __DIR__ . '/../Repositories/ServiceRepository.php';

/**
 * Service Controller
 *
 * Handles service-related HTTP requests with authorization
 */
class ServiceController
{
    private ServiceService $serviceService;

    public function __construct()
    {
        $db                   = require __DIR__ . '/../../config/database.php';
        $repository           = new ServiceRepository($db);
        $this->serviceService = new ServiceService($repository);
    }

    /**
     * Show all services for the authenticated student
     */
    public function index(): void
    {
        $user = Auth::user();

        if (! $user || $user['role'] !== 'student') {
            redirect('/login');
            return;
        }

        $services = $this->serviceService->getStudentServices($user['id']);

        view('student.services.index', [
            'services' => $services,
        ], 'dashboard');
    }

    /**
     * Show service creation form
     */
    public function create(): void
    {
        $user = Auth::user();

        if (! $user) {
            redirect('/login');
            return;
        }

        $servicePolicy = new ServicePolicy();
        if (! $servicePolicy->canCreate($user)) {
            Auth::authorizeOrFail('service', 'create', []);
        }

        $categories = $this->serviceService->getAllCategories();

        view('student.services.create', [
            'categories' => $categories,
        ], 'dashboard');

        // Clear old input after rendering
        clear_old_input();
    }

    /**
     * Store a new service
     */
    public function store(): void
    {
        $user = Auth::user();

        if (! $user || $user['role'] !== 'student') {
            redirect('/login');
            return;
        }

        // Get form data
        $data = [
            'title'         => $_POST['title'] ?? '',
            'description'   => $_POST['description'] ?? '',
            'category_id'   => $_POST['category_id'] ?? '',
            'tags'          => $_POST['tags'] ?? '',
            'price'         => $_POST['price'] ?? '',
            'delivery_days' => $_POST['delivery_days'] ?? '',
        ];

        // Get uploaded files
        $files = [];
        if (isset($_FILES['sample_files']) && is_array($_FILES['sample_files']['name'])) {
            for ($i = 0; $i < count($_FILES['sample_files']['name']); $i++) {
                if ($_FILES['sample_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name'     => $_FILES['sample_files']['name'][$i],
                        'type'     => $_FILES['sample_files']['type'][$i],
                        'tmp_name' => $_FILES['sample_files']['tmp_name'][$i],
                        'error'    => $_FILES['sample_files']['error'][$i],
                        'size'     => $_FILES['sample_files']['size'][$i],
                    ];
                }
            }
        }

        // Create service
        $result = $this->serviceService->createService($user['id'], $data, $files);

        if ($result['success']) {
            flash('success', 'Service created successfully! It will be active once admin approves it.');
            redirect('/student/services');
        } else {
            flash('error', 'Failed to create service. Please check the errors below.');
            flash_input($data);
            $_SESSION['errors'] = $result['errors'];
            redirect('/student/services/create');
        }
    }

    /**
     * Show service detail
     */
    public function show($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can view this service
        Auth::authorizeOrFail('service', 'edit', $service);

        view('student.services.show', [
            'service' => $service,
        ], 'dashboard');
    }

    /**
     * Show edit form for a service
     */
    public function edit($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can edit this service
        Auth::authorizeOrFail('service', 'edit', $service);

        $categories = $this->serviceService->getAllCategories();

        // Get active orders info (set by ServiceEditMiddleware)
        $hasActiveOrders = $_SESSION['service_has_active_orders'] ?? false;
        $activeOrders = $_SESSION['service_active_orders'] ?? [];
        unset($_SESSION['service_has_active_orders']);
        unset($_SESSION['service_active_orders']);

        view('student.services.edit', [
            'service'    => $service,
            'categories' => $categories,
            'hasActiveOrders' => $hasActiveOrders,
            'activeOrders' => $activeOrders,
        ], 'dashboard');

        // Clear old input after rendering
        clear_old_input();
    }

    /**
     * Update a service
     */
    public function update($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can edit this service
        Auth::authorizeOrFail('service', 'edit', $service);

        // Check if service has active orders (set by middleware)
        $hasActiveOrders = $_SESSION['service_has_active_orders'] ?? false;
        unset($_SESSION['service_has_active_orders']);
        unset($_SESSION['service_active_orders']);

        // Get form data
        $data = [
            'title'         => $_POST['title'] ?? '',
            'description'   => $_POST['description'] ?? '',
            'category_id'   => $_POST['category_id'] ?? '',
            'tags'          => $_POST['tags'] ?? '',
            'price'         => $_POST['price'] ?? '',
            'delivery_days' => $_POST['delivery_days'] ?? '',
        ];

        // If service has active orders, remove restricted fields from update
        if ($hasActiveOrders) {
            $restrictedFields = ['price', 'delivery_days', 'description', 'category_id'];
            foreach ($restrictedFields as $field) {
                unset($data[$field]);
            }
        }

        // Get files to remove
        $filesToRemove = $_POST['remove_files'] ?? [];
        if (!is_array($filesToRemove)) {
            $filesToRemove = [];
        }

        // Get uploaded files
        $files = [];
        if (isset($_FILES['sample_files']) && is_array($_FILES['sample_files']['name'])) {
            for ($i = 0; $i < count($_FILES['sample_files']['name']); $i++) {
                if ($_FILES['sample_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name'     => $_FILES['sample_files']['name'][$i],
                        'type'     => $_FILES['sample_files']['type'][$i],
                        'tmp_name' => $_FILES['sample_files']['tmp_name'][$i],
                        'error'    => $_FILES['sample_files']['error'][$i],
                        'size'     => $_FILES['sample_files']['size'][$i],
                    ];
                }
            }
        }

        // Update service
        $result = $this->serviceService->updateService($serviceId, $data, $files, $filesToRemove);

        if ($result['success']) {
            flash('success', 'Service updated successfully!');
            redirect('/student/services');
        } else {
            flash('error', 'Failed to update service. Please check the errors below.');
            flash_input($data);
            $_SESSION['errors'] = $result['errors'];
            redirect('/student/services/' . $serviceId . '/edit');
        }
    }

    /**
     * Delete a service
     */
    public function delete($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can delete this service
        Auth::authorizeOrFail('service', 'delete', $service);

        // Delete service
        $result = $this->serviceService->deleteService($serviceId);

        if ($result['success']) {
            flash('success', 'Service deleted successfully!');
        } else {
            flash('error', $result['errors']['service'] ?? 'Failed to delete service');
        }

        redirect('/student/services');
    }

    /**
     * Activate a service
     */
    public function activate($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can activate this service
        Auth::authorizeOrFail('service', 'activate', $service);

        // Prevent students from activating inactive or rejected services (must be approved by admin)
        // Only allow activating 'paused' services
        if ($service['status'] !== 'paused' && $service['status'] !== 'active') {
            flash('error', 'This service cannot be activated directly. It may require administrator approval.');
            redirect('/student/services');
            return;
        }

        // Activate service
        if ($this->serviceService->activateService($serviceId)) {
            flash('success', 'Service activated successfully! It is now visible to clients.');
        } else {
            flash('error', 'Failed to activate service');
        }

        redirect('/student/services');
    }

    /**
     * Pause a service
     */
    public function pause($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can pause this service
        Auth::authorizeOrFail('service', 'activate', $service);

        // Pause service
        if ($this->serviceService->pauseService($serviceId)) {
            flash('success', 'Service paused successfully! It is no longer visible to clients.');
        } else {
            flash('error', 'Failed to pause service');
        }

        redirect('/student/services');
    }

    /**
     * Deactivate a service
     */
    public function deactivate($id): void
    {
        $serviceId = is_array($id) ? $id['id'] : $id;
        $service   = $this->serviceService->getServiceById($serviceId);

        if (! $service) {
            http_response_code(404);
            require __DIR__ . '/../../views/errors/404.php';
            return;
        }

        // Check if user can deactivate this service (same as edit/activate)
        Auth::authorizeOrFail('service', 'activate', $service);

        // Deactivate service
        if ($this->serviceService->deactivateService($serviceId)) {
            flash('success', 'Service deactivated successfully! It is no longer visible to clients.');
        } else {
            flash('error', 'Failed to deactivate service');
        }

        redirect('/student/services');
    }
}
