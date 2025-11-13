<?php

/**
 * Client Profile Controller
 *
 * Handles client profile HTTP requests including viewing and editing
 * profile information (name and profile picture)
 */
class ClientProfileController
{
    private ClientProfileService $profileService;
    private UserRepository $userRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db             = $this->getDatabase();
        $this->userRepository = new UserRepository($this->db);
        $this->profileService = new ClientProfileService($this->userRepository);
    }

    /**
     * Get database connection
     */
    private function getDatabase(): PDO
    {
        static $db = null;
        if ($db === null) {
            $db = require __DIR__ . '/../../config/database.php';
        }
        return $db;
    }

    /**
     * Show profile edit form
     */
    public function edit(): void
    {
        // Ensure user is authenticated and is a client
        if (! auth() || user_role() !== 'client') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Get user information
            $user = $this->userRepository->findById($userId);

            if (! $user) {
                flash('error', 'User not found');
                redirect('/client/dashboard');
                return;
            }

            // Clear old input if no errors
            if (! isset($_SESSION['errors'])) {
                clear_old_input();
            }

            view('client/profile', [
                'user' => $user,
            ], 'dashboard');
        } catch (Exception $e) {
            flash('error', 'Error loading profile: ' . $e->getMessage());
            redirect('/client/dashboard');
        }
    }

    /**
     * Update profile
     */
    public function update(): void
    {
        // Ensure user is authenticated and is a client
        if (! auth() || user_role() !== 'client') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Validate input
            $errors = $this->validateProfileInput($_POST, $_FILES);

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                flash_input($_POST);
                redirect('/client/profile/edit');
                return;
            }

            // Update profile using service
            $result = $this->profileService->updateProfile($userId, $_POST, $_FILES);

            if (! $result['success']) {
                if (isset($result['errors'])) {
                    $_SESSION['errors'] = $result['errors'];
                } else {
                    $_SESSION['errors'] = ['general' => $result['error'] ?? 'Failed to update profile'];
                }
                flash_input($_POST);
                redirect('/client/profile/edit');
                return;
            }

            flash('success', 'Profile updated successfully');
            redirect('/client/profile/edit');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/client/profile/edit');
        }
    }

    /**
     * Delete profile picture (AJAX endpoint)
     */
    public function deleteProfilePicture(): void
    {
        // Ensure user is authenticated and is a client
        if (! auth() || user_role() !== 'client') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId = user_id();

        try {
            $success = $this->profileService->deleteProfilePicture($userId);

            if ($success) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Profile picture deleted successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to delete profile picture']);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Validate profile input
     */
    private function validateProfileInput(array $data, array $files): array
    {
        $errors = [];

        // Name validation (optional, but if provided should meet requirements)
        if (isset($data['name'])) {
            $name = trim($data['name']);

            if (! empty($name)) {
                if (strlen($name) < 2) {
                    $errors['name'] = 'Name must be at least 2 characters';
                } elseif (strlen($name) > 255) {
                    $errors['name'] = 'Name must not exceed 255 characters';
                }
            }
        }

        // Profile picture validation
        if (! empty($files['profile_picture']) && $files['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $files['profile_picture'];

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['profile_picture'] = 'Error uploading file';
            } else {
                // Validate file size (5MB limit)
                $size = $file['size'] ?? 0;
                if ($size > 5 * 1024 * 1024) {
                    $errors['profile_picture'] = 'Profile picture must not exceed 5MB';
                }

                // Validate file type
                $originalName = $file['name'] ?? '';
                $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (! in_array($extension, $allowedTypes)) {
                    $errors['profile_picture'] = 'Profile picture must be an image file (JPEG, PNG, GIF, or WebP)';
                } else {
                    // Validate it's actually an image
                    $tmpPath   = $file['tmp_name'] ?? '';
                    $imageInfo = @getimagesize($tmpPath);
                    if ($imageInfo === false) {
                        $errors['profile_picture'] = 'Invalid image file';
                    }
                }
            }
        }

        return $errors;
    }
}
