<?php

/**
 * Profile Service
 *
 * Handles student profile business logic
 */
class ProfileService
{
    private StudentProfileRepository $profileRepository;
    private string $uploadPath;

    public function __construct(StudentProfileRepository $profileRepository)
    {
        $this->profileRepository = $profileRepository;
        $this->uploadPath        = __DIR__ . '/../../storage/uploads/profiles';
    }

    /**
     * Get or create profile for user
     */
    public function getOrCreateProfile(int $userId): array
    {
        $profile = $this->profileRepository->findByUserId($userId);

        if (! $profile) {
            // Create empty profile
            $profileId = $this->profileRepository->create(['user_id' => $userId]);
            $profile   = $this->profileRepository->findByUserId($userId);
        }

        // Decode JSON fields
        $profile['skills']          = json_decode($profile['skills'] ?? '[]', true);
        $profile['portfolio_files'] = json_decode($profile['portfolio_files'] ?? '[]', true);

        return $profile;
    }

    /**
     * Update student profile
     */
    public function updateProfile(int $userId, array $data, array $files = []): array
    {
        // Get existing profile
        $profile = $this->getOrCreateProfile($userId);

        // Process bio
        $bio = trim($data['bio'] ?? '');

        // Process skills (comma-separated string to array)
        $skillsInput = trim($data['skills'] ?? '');
        $skills      = [];

        if (! empty($skillsInput)) {
            $skills = array_map('trim', explode(',', $skillsInput));
            $skills = array_filter($skills); // Remove empty values
            $skills = array_values($skills); // Re-index array
        }

        // Process profile picture upload
        $profilePicture = $profile['profile_picture'] ?? null;

        if (! empty($files['profile_picture']['name'])) {
            $uploadedPicture = $this->handleProfilePictureUpload($userId, $files['profile_picture'], $profilePicture);
            if ($uploadedPicture) {
                $profilePicture = $uploadedPicture;
            }
        }

        // Process portfolio file uploads
        $portfolioFiles = $profile['portfolio_files'] ?? [];

        if (! empty($files['portfolio_files']['name'][0])) {
            $uploadedFiles  = $this->handlePortfolioUploads($userId, $files['portfolio_files']);
            $portfolioFiles = array_merge($portfolioFiles, $uploadedFiles);
        }

        // Update profile
        $updateData = [
            'bio'             => $bio,
            'skills'          => json_encode($skills),
            'portfolio_files' => json_encode($portfolioFiles),
        ];

        if ($profilePicture !== null) {
            $updateData['profile_picture'] = $profilePicture;
        }

        $this->profileRepository->update($userId, $updateData);

        return $this->getOrCreateProfile($userId);
    }

    /**
     * Handle profile picture upload
     */
    private function handleProfilePictureUpload(int $userId, array $file, ?string $oldPicture): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = $file['tmp_name'];
        $name    = $file['name'];
        $size    = $file['size'];

        // Validate file
        $validation = $this->validateProfilePicture($name, $size, $tmpName);

        if ($validation !== true) {
            throw new Exception($validation);
        }

        // Create user directory if it doesn't exist
        $userDir = $this->uploadPath . '/' . $userId;

        if (! is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // Delete old profile picture if exists
        if ($oldPicture && file_exists($userDir . '/' . $oldPicture)) {
            unlink($userDir . '/' . $oldPicture);
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $filename  = 'profile_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $filepath  = $userDir . '/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($tmpName, $filepath)) {
            return $filename;
        }

        return null;
    }

    /**
     * Validate profile picture
     */
    private function validateProfilePicture(string $filename, int $size, string $tmpPath): string | bool
    {
                                    // Check file size (5MB limit for profile pictures)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes

        if ($size > $maxSize) {
            return 'Profile picture size exceeds 5MB limit';
        }

        // Check file extension (only images)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $extension         = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedExtensions)) {
            return 'Profile picture must be an image (JPG, PNG, or GIF)';
        }

        // Validate MIME type
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];

        if (! in_array($mimeType, $allowedMimeTypes)) {
            return 'Invalid image file';
        }

        // Verify it's a valid image
        $imageInfo = @getimagesize($tmpPath);

        if ($imageInfo === false) {
            return 'Invalid image file';
        }

        return true;
    }

    /**
     * Handle portfolio file uploads
     */
    private function handlePortfolioUploads(int $userId, array $files): array
    {
        $uploadedFiles = [];

        // Create user directory if it doesn't exist
        $userDir = $this->uploadPath . '/' . $userId;

        if (! is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // Process each file
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName  = $files['tmp_name'][$i];
            $name     = $files['name'][$i];
            $size     = $files['size'][$i];
            $mimeType = $files['type'][$i];

            // Validate file
            $validation = $this->validatePortfolioFile($name, $size, $tmpName);

            if ($validation !== true) {
                throw new Exception($validation);
            }

            // Generate unique filename
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $filename  = bin2hex(random_bytes(16)) . '.' . $extension;
            $filepath  = $userDir . '/' . $filename;

            // Move uploaded file
            if (move_uploaded_file($tmpName, $filepath)) {
                $uploadedFiles[] = [
                    'filename'      => $filename,
                    'original_name' => $name,
                    'size'          => $size,
                    'mime_type'     => $mimeType,
                    'uploaded_at'   => date('Y-m-d H:i:s'),
                ];
            }
        }

        return $uploadedFiles;
    }

    /**
     * Validate portfolio file
     */
    private function validatePortfolioFile(string $filename, int $size, string $tmpPath): string | bool
    {
                                     // Check file size (10MB limit)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes

        if ($size > $maxSize) {
            return 'File size exceeds 10MB limit';
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip'];
        $extension         = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedExtensions)) {
            return 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
        }

        // Validate MIME type
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ];

        if (! in_array($mimeType, $allowedMimeTypes)) {
            return 'Invalid file type';
        }

        // For images, verify they are valid
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imageInfo = @getimagesize($tmpPath);

            if ($imageInfo === false) {
                return 'Invalid image file';
            }
        }

        return true;
    }

    /**
     * Delete portfolio file
     */
    public function deletePortfolioFile(int $userId, string $filename): bool
    {
        $profile = $this->getOrCreateProfile($userId);

        // Find and remove file from array
        $portfolioFiles = $profile['portfolio_files'];
        $updatedFiles   = [];

        foreach ($portfolioFiles as $file) {
            if ($file['filename'] !== $filename) {
                $updatedFiles[] = $file;
            }
        }

        // Delete physical file
        $filepath = $this->uploadPath . '/' . $userId . '/' . $filename;

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Update database
        return $this->profileRepository->update($userId, [
            'bio'             => $profile['bio'],
            'skills'          => json_encode($profile['skills']),
            'portfolio_files' => json_encode($updatedFiles),
        ]);
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(int $userId): bool
    {
        $profile = $this->getOrCreateProfile($userId);

        if (empty($profile['profile_picture'])) {
            return false;
        }

        // Delete physical file
        $filepath = $this->uploadPath . '/' . $userId . '/' . $profile['profile_picture'];

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Update database
        return $this->profileRepository->update($userId, [
            'bio'             => $profile['bio'],
            'skills'          => json_encode($profile['skills']),
            'portfolio_files' => json_encode($profile['portfolio_files']),
            'profile_picture' => null,
        ]);
    }

    /**
     * Get profile with user information
     */
    public function getProfileWithUser(int $userId): ?array
    {
        $profile = $this->profileRepository->getProfileWithUser($userId);

        if ($profile) {
            $profile['skills']          = json_decode($profile['skills'] ?? '[]', true);
            $profile['portfolio_files'] = json_decode($profile['portfolio_files'] ?? '[]', true);
        }

        return $profile;
    }
}
