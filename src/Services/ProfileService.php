<?php

require_once __DIR__ . '/FileService.php';

/**
 * Profile Service
 *
 * Handles student profile business logic
 */
class ProfileService
{
    private StudentProfileRepository $profileRepository;
    private FileService $fileService;
    private string $uploadPath;

    public function __construct(StudentProfileRepository $profileRepository)
    {
        $this->profileRepository = $profileRepository;
        $this->fileService       = new FileService();
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
        // Delete old profile picture if exists
        if ($oldPicture) {
            $oldPath = 'profiles/' . $userId . '/' . $oldPicture;
            $this->fileService->delete($oldPath);
        }

        // Upload new profile picture using FileService
        $result = $this->fileService->upload($file, 'profiles', $userId);

        if (! $result['success']) {
            throw new Exception($result['error']);
        }

        // Return just the filename (not the full path)
        return $result['file']['filename'];
    }

    /**
     * Handle portfolio file uploads
     */
    private function handlePortfolioUploads(int $userId, array $files): array
    {
        // Return empty array if no files provided
        if (empty($files)) {
            return [];
        }

        // Use FileService to upload multiple files
        $result = $this->fileService->uploadMultiple($files, 'profiles', $userId);

        if (! $result['success'] && ! empty($result['errors'])) {
            throw new Exception('File upload failed: ' . implode(', ', $result['errors']));
        }

        return $result['files'];
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
