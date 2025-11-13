<?php

/**
 * Client Profile Service
 *
 * Handles business logic for client profile management including
 * profile updates, picture uploads, and picture deletion
 */
class ClientProfileService
{
    private UserRepository $userRepository;
    private FileService $fileService;
    private string $uploadPath;
    private const PROFILE_PICTURE_SIZE_LIMIT = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_IMAGE_TYPES        = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->fileService    = new FileService();
        $this->uploadPath     = 'client-profiles';
    }

    /**
     * Update client profile (name and/or profile picture)
     *
     * @param int $userId The user ID
     * @param array $data Profile data (name)
     * @param array $files Uploaded files (profile_picture)
     * @return array Result with success status and error/data
     */
    public function updateProfile(int $userId, array $data, array $files = []): array
    {
        $user = $this->userRepository->findById($userId);
        if (! $user) {
            return [
                'success' => false,
                'error'   => 'User not found',
            ];
        }

        $errors  = [];
        $updated = false;

        // Update name if provided
        if (isset($data['name'])) {
            $name = trim($data['name']);

            // Validate name length if not empty
            if (! empty($name)) {
                if (strlen($name) < 2) {
                    $errors['name'] = 'Name must be at least 2 characters';
                } elseif (strlen($name) > 255) {
                    $errors['name'] = 'Name must not exceed 255 characters';
                } else {
                    // Update name in database
                    if ($this->userRepository->updateName($userId, $name)) {
                        $updated = true;
                    } else {
                        $errors['name'] = 'Failed to update name';
                    }
                }
            } else {
                // Allow empty name (set to null)
                if ($this->userRepository->updateName($userId, '')) {
                    $updated = true;
                }
            }
        }

        // Handle profile picture upload if provided
        if (! empty($files['profile_picture']) && $files['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $pictureResult = $this->handleProfilePictureUpload(
                $userId,
                $files['profile_picture'],
                $user['profile_picture']
            );

            if ($pictureResult['success']) {
                $updated = true;
            } else {
                $errors['profile_picture'] = $pictureResult['error'];
            }
        }

        if (! empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
            ];
        }

        if (! $updated) {
            return [
                'success' => false,
                'error'   => 'No changes to update',
            ];
        }

        return [
            'success' => true,
            'message' => 'Profile updated successfully',
        ];
    }

    /**
     * Handle profile picture upload
     *
     * @param int $userId The user ID
     * @param array $file The uploaded file
     * @param string|null $oldPicture The old profile picture path
     * @return array Result with success status and error/path
     */
    private function handleProfilePictureUpload(int $userId, array $file, ?string $oldPicture): array
    {
        // Check for upload errors
        if (! isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error'   => $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        // Validate file size
        $size = $file['size'] ?? 0;
        if ($size > self::PROFILE_PICTURE_SIZE_LIMIT) {
            return [
                'success' => false,
                'error'   => 'Profile picture must not exceed 5MB',
            ];
        }

        // Validate file type
        $originalName = $file['name'] ?? '';
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_IMAGE_TYPES)) {
            return [
                'success' => false,
                'error'   => 'Profile picture must be an image file (JPEG, PNG, GIF, or WebP)',
            ];
        }

        // Validate it's actually an image
        $tmpPath   = $file['tmp_name'] ?? '';
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            return [
                'success' => false,
                'error'   => 'Invalid image file',
            ];
        }

        // Upload the file using FileService
        $uploadResult = $this->fileService->upload($file, $this->uploadPath, $userId);

        if (! $uploadResult['success']) {
            return [
                'success' => false,
                'error'   => $uploadResult['error'],
            ];
        }

        $newPicturePath = $uploadResult['file']['path'];

        // Update database with new profile picture path
        if (! $this->userRepository->updateProfilePicture($userId, $newPicturePath)) {
            // If database update fails, delete the uploaded file
            $this->fileService->delete($newPicturePath);
            return [
                'success' => false,
                'error'   => 'Failed to update profile picture',
            ];
        }

        // Delete old profile picture if it exists
        if ($oldPicture && $this->fileService->exists($oldPicture)) {
            $this->fileService->delete($oldPicture);
        }

        return [
            'success' => true,
            'path'    => $newPicturePath,
        ];
    }

    /**
     * Delete profile picture
     *
     * @param int $userId The user ID
     * @return bool True if successful, false otherwise
     */
    public function deleteProfilePicture(int $userId): bool
    {
        $user = $this->userRepository->findById($userId);
        if (! $user || ! $user['profile_picture']) {
            return false;
        }

        $picturePath = $user['profile_picture'];

        // Remove from database first
        if (! $this->userRepository->updateProfilePicture($userId, null)) {
            return false;
        }

        // Delete the file from storage
        if ($this->fileService->exists($picturePath)) {
            $this->fileService->delete($picturePath);
        }

        return true;
    }

    /**
     * Get upload error message
     *
     * @param int $errorCode The upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
            default               => 'Unknown upload error',
        };
    }
}
