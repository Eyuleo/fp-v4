<?php

require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Validators/ServiceValidator.php';

/**
 * Service Service
 *
 * Business logic for service management
 */
class ServiceService
{
    private ServiceRepository $repository;
    private ServiceValidator $validator;

    public function __construct(ServiceRepository $repository)
    {
        $this->repository = $repository;
        $this->validator  = new ServiceValidator();
    }

    /**
     * Create a new service
     *
     * @param int $studentId
     * @param array $data
     * @param array $files
     * @return array ['success' => bool, 'service_id' => int|null, 'errors' => array]
     */
    public function createService(int $studentId, array $data, array $files = []): array
    {
        // Validate input data
        if (! $this->validator->validateCreate($data)) {
            return [
                'success'    => false,
                'service_id' => null,
                'errors'     => $this->validator->getErrors(),
            ];
        }

        // Validate files if provided
        if (! empty($files)) {
            if (! $this->validator->validateFiles($files)) {
                return [
                    'success'    => false,
                    'service_id' => null,
                    'errors'     => $this->validator->getErrors(),
                ];
            }
        }

        // Process tags
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
            $data['tags'] = array_filter($data['tags']); // Remove empty values
        }

        // Prepare service data
        $serviceData = [
            'student_id'    => $studentId,
            'category_id'   => $data['category_id'],
            'title'         => trim($data['title']),
            'description'   => trim($data['description']),
            'tags'          => $data['tags'] ?? [],
            'price'         => (float) $data['price'],
            'delivery_days' => (int) $data['delivery_days'],
            'sample_files'  => [],
            'status'        => 'inactive', // Always create as inactive
        ];

        // Create service first to get ID
        $serviceId = $this->repository->create($serviceData);

        // Handle file uploads if provided
        if (! empty($files) && $serviceId) {
            $uploadedFiles = $this->handleFileUploads($serviceId, $files);

            if (! empty($uploadedFiles)) {
                // Update service with file paths
                $this->repository->update($serviceId, [
                    'sample_files' => $uploadedFiles,
                ]);
            }
        }

        return [
            'success'    => true,
            'service_id' => $serviceId,
            'errors'     => [],
        ];
    }

    /**
     * Update a service
     *
     * @param int $serviceId
     * @param array $data
     * @param array $files
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateService(int $serviceId, array $data, array $files = []): array
    {
        // Validate input data
        if (! $this->validator->validateUpdate($data)) {
            return [
                'success' => false,
                'errors'  => $this->validator->getErrors(),
            ];
        }

        // Validate files if provided
        if (! empty($files)) {
            if (! $this->validator->validateFiles($files)) {
                return [
                    'success' => false,
                    'errors'  => $this->validator->getErrors(),
                ];
            }
        }

        // Process tags
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_map('trim', explode(',', $data['tags']));
            $data['tags'] = array_filter($data['tags']);
        }

        // Prepare update data
        $updateData = [];

        if (isset($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        }
        if (isset($data['title'])) {
            $updateData['title'] = trim($data['title']);
        }
        if (isset($data['description'])) {
            $updateData['description'] = trim($data['description']);
        }
        if (isset($data['tags'])) {
            $updateData['tags'] = $data['tags'];
        }
        if (isset($data['price'])) {
            $updateData['price'] = (float) $data['price'];
        }
        if (isset($data['delivery_days'])) {
            $updateData['delivery_days'] = (int) $data['delivery_days'];
        }

        // Handle file uploads if provided
        if (! empty($files)) {
            $uploadedFiles = $this->handleFileUploads($serviceId, $files);

            if (! empty($uploadedFiles)) {
                // Get existing files
                $service       = $this->repository->findById($serviceId);
                $existingFiles = $service['sample_files'] ?? [];

                // Merge with new files
                $updateData['sample_files'] = array_merge($existingFiles, $uploadedFiles);
            }
        }

        // Update service
        $success = $this->repository->update($serviceId, $updateData);

        return [
            'success' => $success,
            'errors'  => [],
        ];
    }

    /**
     * Delete a service
     *
     * @param int $serviceId
     * @return array ['success' => bool, 'errors' => array]
     */
    public function deleteService(int $serviceId): array
    {
        // Check if service has active orders
        if ($this->repository->hasActiveOrders($serviceId)) {
            return [
                'success' => false,
                'errors'  => ['service' => 'Cannot delete service with active orders'],
            ];
        }

        // Get service to delete files
        $service = $this->repository->findById($serviceId);

        if ($service) {
            // Delete associated files
            $this->deleteServiceFiles($serviceId, $service['sample_files']);
        }

        // Delete service
        $success = $this->repository->delete($serviceId);

        return [
            'success' => $success,
            'errors'  => [],
        ];
    }

    /**
     * Activate a service
     *
     * @param int $serviceId
     * @return bool
     */
    public function activateService(int $serviceId): bool
    {
        return $this->repository->update($serviceId, ['status' => 'active']);
    }

    /**
     * Pause a service
     *
     * @param int $serviceId
     * @return bool
     */
    public function pauseService(int $serviceId): bool
    {
        return $this->repository->update($serviceId, ['status' => 'paused']);
    }

    /**
     * Get service by ID
     *
     * @param int $serviceId
     * @return array|null
     */
    public function getServiceById(int $serviceId): ?array
    {
        return $this->repository->findById($serviceId);
    }

    /**
     * Get all services for a student
     *
     * @param int $studentId
     * @return array
     */
    public function getStudentServices(int $studentId): array
    {
        return $this->repository->findByStudentId($studentId);
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

    /**
     * Handle file uploads
     *
     * @param int $serviceId
     * @param array $files
     * @return array Array of file paths
     */
    private function handleFileUploads(int $serviceId, array $files): array
    {
        $uploadedFiles = [];
        $uploadDir     = __DIR__ . '/../../storage/uploads/services/' . $serviceId;

        // Create directory if it doesn't exist
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Generate unique filename
                $extension   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $filename    = uniqid() . '_' . time() . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $uploadedFiles[] = [
                        'filename'      => $filename,
                        'original_name' => $file['name'],
                        'path'          => 'storage/uploads/services/' . $serviceId . '/' . $filename,
                        'size'          => $file['size'],
                    ];
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete service files
     *
     * @param int $serviceId
     * @param array $files
     * @return void
     */
    private function deleteServiceFiles(int $serviceId, array $files): void
    {
        $uploadDir = __DIR__ . '/../../storage/uploads/services/' . $serviceId;

        foreach ($files as $file) {
            $filePath = __DIR__ . '/../../' . $file['path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Remove directory if empty
        if (is_dir($uploadDir) && count(scandir($uploadDir)) === 2) {
            rmdir($uploadDir);
        }
    }
}
