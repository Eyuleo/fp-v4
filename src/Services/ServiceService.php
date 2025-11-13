<?php

require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Validators/ServiceValidator.php';
require_once __DIR__ . '/FileService.php';

/**
 * Service Service
 *
 * Business logic for service management
 */
class ServiceService
{
    private ServiceRepository $repository;
    private ServiceValidator $validator;
    private FileService $fileService;

    public function __construct(ServiceRepository $repository)
    {
        $this->repository  = $repository;
        $this->validator   = new ServiceValidator();
        $this->fileService = new FileService();
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

        // Begin transaction
        $this->repository->beginTransaction();

        try {
            // Create service first to get ID
            $serviceId = $this->repository->create($serviceData);

            // Handle sample files
            if (! empty($files)) {
                $fileService  = new FileService();
                $uploadResult = $fileService->uploadMultiple($files, 'services', $serviceId);

                if ($uploadResult['success'] && ! empty($uploadResult['files'])) {
                    $this->repository->update($serviceId, [
                        'sample_files' => $uploadResult['files'],
                    ]);
                } elseif (! empty($uploadResult['errors'])) {
                    // Optionally collect errors
                    $errors['sample_files'] = implode(', ', $uploadResult['errors']);
                }
            }

            // Commit transaction
            $this->repository->commit();

            return [
                'success'    => true,
                'service_id' => $serviceId,
                'errors'     => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->repository->rollback();
            error_log('Service creation error: ' . $e->getMessage());

            return [
                'success'    => false,
                'service_id' => null,
                'errors'     => ['database' => 'Failed to create service. Please try again.'],
            ];
        }
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

        // Begin transaction
        $this->repository->beginTransaction();

        try {
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

            // Commit transaction
            $this->repository->commit();

            return [
                'success' => $success,
                'errors'  => [],
            ];
        } catch (Exception $e) {
            // Rollback on error
            $this->repository->rollback();
            error_log('Service update error: ' . $e->getMessage());

            return [
                'success' => false,
                'errors'  => ['database' => 'Failed to update service. Please try again.'],
            ];
        }
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
     * @return array Array of file metadata
     */
    private function handleFileUploads(int $serviceId, array $files): array
    {
        // Return empty array if no files provided
        if (empty($files)) {
            return [];
        }

        // Use FileService to upload multiple files
        $result = $this->fileService->uploadMultiple($files, 'services', $serviceId);

        // Only throw exception if there were actual errors (not just no files)
        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }

        return $result['files'];
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
        foreach ($files as $file) {
            // Files are stored with path like 'services/123/filename.ext'
            $path = $file['path'] ?? '';
            if ($path) {
                $this->fileService->delete($path);
            }
        }
    }
}
