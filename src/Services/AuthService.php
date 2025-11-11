<?php

/**
 * Authentication Service
 *
 * Handles user authentication business logic
 */
class AuthService
{
    private UserRepository $userRepository;
    private MailService $mailService;

    public function __construct(UserRepository $userRepository, MailService $mailService)
    {
        $this->userRepository = $userRepository;
        $this->mailService    = $mailService;
    }

    /**
     * Register a new user
     */
    public function register(string $email, string $password, string $role): array
    {
        // Check if email already exists
        if ($this->userRepository->emailExists($email)) {
            throw new Exception('Email already registered');
        }

        // Validate role
        if (! in_array($role, ['student', 'client'])) {
            throw new Exception('Invalid role');
        }

        // Hash password with bcrypt cost 12
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Generate 32-byte verification token
        $verificationToken = bin2hex(random_bytes(32));

        // Create user
        $userId = $this->userRepository->create([
            'email'              => $email,
            'password_hash'      => $passwordHash,
            'role'               => $role,
            'status'             => 'unverified',
            'verification_token' => $verificationToken,
        ]);

        // Get the created user
        $user = $this->userRepository->findById($userId);

        // Send verification email
        $this->sendVerificationEmail($user);

        return $user;
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(array $user): void
    {
        $appUrl          = getenv('APP_URL') ?: 'http://localhost:8000';
        $verificationUrl = $appUrl . '/auth/verify-email?token=' . $user['verification_token'];

        $subject = 'Verify Your Email Address';
        $data    = [
            'email'            => $user['email'],
            'verification_url' => $verificationUrl,
        ];

        $this->mailService->send($user['email'], $subject, 'emails/verification', $data);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if (! $user) {
            throw new Exception('Invalid or expired verification token');
        }

        // Check if token is expired (24 hours)
        $createdAt = new DateTime($user['created_at']);
        $now       = new DateTime();
        $diff      = $now->diff($createdAt);

        if ($diff->days >= 1) {
            throw new Exception('Verification token has expired');
        }

        // Verify the email
        return $this->userRepository->verifyEmail($user['id']);
    }

    /**
     * Authenticate user
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            throw new Exception('Invalid credentials');
        }

        // Check if email is verified
        if ($user['status'] === 'unverified') {
            throw new Exception('Please verify your email address before logging in');
        }

        // Check if account is suspended
        if ($user['status'] === 'suspended') {
            throw new Exception('Your account has been suspended');
        }

        // Verify password
        if (! password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }

        return $user;
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            // Don't reveal if email exists
            return;
        }

        // Generate 32-byte reset token
        $resetToken = bin2hex(random_bytes(32));

        // Set expiration to 1 hour from now
        $expiresAt = (new DateTime())->add(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

        // Save reset token
        $this->userRepository->setResetToken($user['id'], $resetToken, $expiresAt);

        // Send reset email
        $this->sendPasswordResetEmail($user, $resetToken);
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(array $user, string $token): void
    {
        $appUrl   = getenv('APP_URL') ?: 'http://localhost:8000';
        $resetUrl = $appUrl . '/auth/reset-password?token=' . $token;

        $subject = 'Reset Your Password';
        $data    = [
            'email'     => $user['email'],
            'reset_url' => $resetUrl,
        ];

        $this->mailService->send($user['email'], $subject, 'emails/password-reset', $data);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findByResetToken($token);

        if (! $user) {
            throw new Exception('Invalid or expired reset token');
        }

        // Hash new password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // Update password and clear reset token
        return $this->userRepository->updatePassword($user['id'], $passwordHash);
    }

    /**
     * Create authenticated session
     */
    public function createSession(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        // Regenerate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Destroy authenticated session
     */
    public function destroySession(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
