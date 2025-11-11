<?php

/**
 * Settings Controller
 *
 * Handles user account settings
 */
class SettingsController
{
    private UserRepository $userRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db             = $this->getDatabase();
        $this->userRepository = new UserRepository($this->db);
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
     * Show account settings page
     */
    public function account(): void
    {
        if (! auth()) {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();
        $user   = $this->userRepository->findById($userId);

        if (! $user) {
            flash('error', 'User not found');
            redirect('/');
            return;
        }

        // Clear old input if no errors
        if (! isset($_SESSION['errors'])) {
            clear_old_input();
        }

        view('settings/account', ['user' => $user], 'dashboard');
    }

    /**
     * Update account information
     */
    public function updateAccount(): void
    {
        if (! auth()) {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            $name  = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            $errors = [];

            // Name validation
            if (! empty($name) && strlen($name) > 255) {
                $errors['name'] = 'Name must not exceed 255 characters';
            }

            // Email validation
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } else {
                // Check if email is already taken by another user
                $existingUser = $this->userRepository->findByEmail($email);
                if ($existingUser && $existingUser['id'] !== $userId) {
                    $errors['email'] = 'Email is already taken';
                }
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                flash_input($_POST);
                redirect('/settings/account');
                return;
            }

            // Update user
            $this->userRepository->updateAccount($userId, $name, $email);

            // Update session email
            $_SESSION['user_email'] = $email;

            flash('success', 'Account information updated successfully');
            redirect('/settings/account');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/settings/account');
        }
    }

    /**
     * Update password
     */
    public function updatePassword(): void
    {
        if (! auth()) {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = [];

            // Get user
            $user = $this->userRepository->findById($userId);

            // Validate current password
            if (empty($currentPassword)) {
                $errors['current_password'] = 'Current password is required';
            } elseif (! password_verify($currentPassword, $user['password_hash'])) {
                $errors['current_password'] = 'Current password is incorrect';
            }

            // Validate new password
            if (empty($newPassword)) {
                $errors['new_password'] = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $errors['new_password'] = 'Password must be at least 8 characters';
            }

            // Validate password confirmation
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                redirect('/settings/account');
                return;
            }

            // Hash new password
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

            // Update password
            $this->userRepository->updatePassword($userId, $passwordHash);

            flash('success', 'Password updated successfully');
            redirect('/settings/account');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            redirect('/settings/account');
        }
    }
}
