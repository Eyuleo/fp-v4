<?php

/**
 * Authentication Controller
 *
 * Handles authentication-related HTTP requests
 */
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $db                = require __DIR__ . '/../../config/database.php';
        $userRepository    = new UserRepository($db);
        $mailService       = new MailService();
        $this->authService = new AuthService($userRepository, $mailService);
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        // Clear old input if no errors
        if (! isset($_SESSION['errors'])) {
            clear_old_input();
        }

        view('auth/register', [], 'auth');
    }

    /**
     * Handle registration
     */
    public function register(): void
    {
        try {
            // Validate input
            $name            = trim($_POST['name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $password        = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $role            = $_POST['role'] ?? '';

            $errors = [];

            // Name validation
            if (empty($name)) {
                $errors['name'] = 'Name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Name must be at least 3 characters';
            }

            // Email validation
            if (empty($email)) {
                $errors['email'] = 'Email is required';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }

            // Password validation
            if (empty($password)) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }

            // Password confirmation
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Passwords do not match';
            }

            // Role validation
            if (empty($role)) {
                $errors['role'] = 'Please select a role';
            } elseif (! in_array($role, ['student', 'client'])) {
                $errors['role'] = 'Invalid role selected';
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                flash_input($_POST);
                redirect('/auth/register');
                return;
            }

            // Register user
            $user = $this->authService->register($email, $password, $role, $name);

            // Success message
            flash('success', 'Registration successful! Please check your email to verify your account.');
            redirect('/auth/login');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/auth/register');
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(): void
    {
        try {
            $token = $_GET['token'] ?? '';

            if (empty($token)) {
                throw new Exception('Verification token is required');
            }

            $this->authService->verifyEmail($token);

            view('auth/verify-email', ['success' => true], 'auth');
        } catch (Exception $e) {
            view('auth/verify-email', [
                'success' => false,
                'error'   => $e->getMessage(),
            ], 'auth');
        }
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // Clear old input if no errors
        if (! isset($_SESSION['errors'])) {
            clear_old_input();
        }

        view('auth/login', [], 'auth');
    }

    /**
     * Handle login
     */
    public function login(): void
    {
        try {
            // Validate input
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

            $errors = [];

            if (empty($email)) {
                $errors['email'] = 'Email is required';
            }

            if (empty($password)) {
                $errors['password'] = 'Password is required';
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                flash_input($_POST);
                redirect('/auth/login');
                return;
            }

            // Authenticate user
            $user = $this->authService->login($email, $password);

            // Create session with remember me option
            $this->authService->createSession($user, $remember);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('/admin/dashboard');
            } elseif ($user['role'] === 'student') {
                redirect('/student/dashboard');
            } else {
                redirect('/client/dashboard');
            }
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/auth/login');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        $this->authService->destroySession();
        flash('success', 'You have been logged out successfully');
        redirect('/');
    }

    /**
     * Show password reset request form
     */
    public function showRequestReset(): void
    {
        // Clear old input if no errors
        if (! isset($_SESSION['errors'])) {
            clear_old_input();
        }

        view('auth/request-reset', [], 'auth');
    }

    /**
     * Handle password reset request
     */
    public function requestReset(): void
    {
        try {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $_SESSION['errors'] = ['email' => 'Email is required'];
                flash_input($_POST);
                redirect('/auth/request-reset');
                return;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['errors'] = ['email' => 'Invalid email format'];
                flash_input($_POST);
                redirect('/auth/request-reset');
                return;
            }

            // Request password reset
            $this->authService->requestPasswordReset($email);

            // Always show success message (don't reveal if email exists)
            flash('success', 'If an account exists with that email, you will receive password reset instructions.');
            redirect('/auth/login');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/auth/request-reset');
        }
    }

    /**
     * Show password reset form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            flash('error', 'Invalid reset link');
            redirect('/auth/login');
            return;
        }

        // Clear old input if no errors
        if (! isset($_SESSION['errors'])) {
            clear_old_input();
        }

        view('auth/reset-password', ['token' => $token], 'auth');
    }

    /**
     * Handle password reset
     */
    public function resetPassword(): void
    {
        try {
            $token           = $_POST['token'] ?? '';
            $password        = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            $errors = [];

            if (empty($token)) {
                throw new Exception('Invalid reset token');
            }

            if (empty($password)) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($password) < 8) {
                $errors['password'] = 'Password must be at least 8 characters';
            }

            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Passwords do not match';
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                redirect('/auth/reset-password?token=' . urlencode($token));
                return;
            }

            // Reset password
            $this->authService->resetPassword($token, $password);

            flash('success', 'Your password has been reset successfully. You can now login.');
            redirect('/auth/login');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            redirect('/auth/reset-password?token=' . urlencode($token ?? ''));
        }
    }
}
