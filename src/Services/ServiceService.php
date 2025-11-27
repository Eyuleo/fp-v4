<?php

require_once __DIR__ . '/../Repositories/ServiceRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Validators/ServiceValidator.php';
require_once __DIR__ . '/FileService.php';
require_once __DIR__ . '/../Models/ServiceEditHistory.php';

/**
 * Service Service
 *
 * Business logic for service management
 */
class ServiceService
{
    private ServiceRepository $repository;
    private UserRepository $userRepository;
    private ServiceValidator $validator;
    private FileService $fileService;
    private ServiceEditHistory $editHistory;

    public function __construct(ServiceRepository $repository)
    {
        $this->repository     = $repository;
        $this->userRepository = new UserRepository($repository->getDb());
        $this->validator      = new ServiceValidator();
        $this->fileService    = new FileService();
        $this->editHistory    = new ServiceEditHistory($repository->getDb());
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
        // Check if student has active suspension
        $suspensionStatus = $this->userRepository->checkSuspensionStatus($studentId);
        if ($suspensionStatus['is_suspended']) {
            $errorMessage = 'Your account is currently suspended and you cannot create service listings.';
            if ($suspensionStatus['suspension_end_date']) {
                $errorMessage .= ' Your suspension will end on ' . date('F j, Y', strtotime($suspensionStatus['suspension_end_date'])) . '.';
            }
            return [
                'success'    => false,
                'service_id' => null,
                'errors'     => ['suspension' => $errorMessage],
            ];
        }

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
     * @param array $filesToRemove Array of file paths to remove
     * @return array ['success' => bool, 'errors' => array]
     */
    public function updateService(int $serviceId, array $data, array $files = [], array $filesToRemove = []): array
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

        // Get current service data for audit logging
        $currentService = $this->repository->findById($serviceId);
        if (!$currentService) {
            return [
                'success' => false,
                'errors'  => ['service' => 'Service not found'],
            ];
        }

        // Check if service has active orders
        $hasActiveOrders = $this->repository->hasActiveOrders($serviceId);

        // Get current user ID for audit logging
        $userId = user_id();

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

        // Track if this is a resubmission for notification purposes
        $isResubmission = false;
        $previousRejectionReason = null;

        // If service is rejected, change status to pending on resubmission
        if ($currentService['status'] === 'rejected') {
            $isResubmission = true;
            $previousRejectionReason = $currentService['rejection_reason'] ?? 'No reason provided';
            $updateData['status'] = 'pending';
            $updateData['rejection_reason'] = null;
            $updateData['rejected_at'] = null;
            $updateData['rejected_by'] = null;
        }

        // Begin transaction
        $this->repository->beginTransaction();

        try {
            // Get existing files
            $service       = $this->repository->findById($serviceId);
            $existingFiles = $service['sample_files'] ?? [];

            // Handle file removal
            if (! empty($filesToRemove)) {
                foreach ($filesToRemove as $filePathToRemove) {
                    // Remove from existing files array
                    $existingFiles = array_filter($existingFiles, function($file) use ($filePathToRemove) {
                        return ($file['path'] ?? '') !== $filePathToRemove;
                    });
                    
                    // Delete the actual file
                    $this->fileService->delete($filePathToRemove);
                }
                
                // Re-index array after filtering
                $existingFiles = array_values($existingFiles);
            }

            // Handle file uploads if provided
            if (! empty($files)) {
                $uploadedFiles = $this->handleFileUploads($serviceId, $files);

                if (! empty($uploadedFiles)) {
                    // Merge with existing files (after removal)
                    $existingFiles = array_merge($existingFiles, $uploadedFiles);
                }
            }

            // Update sample_files if there were any changes
            if (! empty($filesToRemove) || ! empty($files)) {
                $updateData['sample_files'] = $existingFiles;
            }

            // Log changes before updating
            foreach ($updateData as $field => $newValue) {
                if ($field === 'sample_files') {
                    // Skip sample_files from audit log as they're tracked separately
                    continue;
                }

                $oldValue = $currentService[$field] ?? null;

                // Only log if value actually changed
                if ($oldValue != $newValue) {
                    $this->editHistory->logEdit(
                        $serviceId,
                        $userId,
                        $field,
                        $oldValue,
                        $newValue,
                        $hasActiveOrders
                    );
                }
            }

            // Update service
            $success = $this->repository->update($serviceId, $updateData);

            // Update last_modified_at timestamp
            $this->repository->update($serviceId, ['last_modified_at' => date('Y-m-d H:i:s')]);

            // Commit transaction
            $this->repository->commit();

            // Send resubmission notification to admins if this was a resubmission
            if ($isResubmission && $previousRejectionReason) {
                // Get student details
                require_once __DIR__ . '/../Repositories/UserRepository.php';
                $userRepository = new UserRepository($this->repository->getDb());
                $student = $userRepository->findById($currentService['student_id']);

                if ($student) {
                    // Get updated service data
                    $updatedService = $this->repository->findById($serviceId);

                    // Send notification
                    require_once __DIR__ . '/NotificationService.php';
                    require_once __DIR__ . '/MailService.php';
                    require_once __DIR__ . '/../Repositories/NotificationRepository.php';

                    $mailService = new MailService();
                    $notificationRepository = new NotificationRepository($this->repository->getDb());
                    $notificationService = new NotificationService($mailService, $notificationRepository);

                    $notificationService->notifyAdminsOfServiceResubmission(
                        $updatedService,
                        $student,
                        $previousRejectionReason
                    );
                }
            }

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
     * Deactivate a service
     *
     * @param int $serviceId
     * @return bool
     */
    public function deactivateService(int $serviceId): bool
    {
        return $this->repository->update($serviceId, ['status' => 'paused']);
    }

    /**
     * Pause all active services for a student
     *
     * @param int $studentId
     * @return bool
     */
    public function pauseAllServicesForStudent(int $studentId): bool
    {
        return $this->repository->updateStatusByStudentId($studentId, 'paused');
    }

    /**
     * Reactivate all paused services for a student
     *
     * @param int $studentId
     * @return bool
     */
    public function reactivateAllServicesForStudent(int $studentId): bool
    {
        return $this->repository->activatePausedServicesByStudentId($studentId);
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
