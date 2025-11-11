<?php

/**
 * Profile Controller
 *
 * Handles student profile HTTP requests
 */
class ProfileController
{
    private ProfileService $profileService;
    private UserRepository $userRepository;
    private PDO $db;

    public function __construct()
    {
        $this->db             = $this->getDatabase();
        $profileRepository    = new StudentProfileRepository($this->db);
        $this->profileService = new ProfileService($profileRepository);
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
     * Get base URL from request
     */
    private function getBaseUrl(): string
    {
        $protocol = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Show profile edit form
     */
    public function edit(): void
    {
        // Ensure user is authenticated and is a student
        if (! auth() || user_role() !== 'student') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Get or create profile
            $profile = $this->profileService->getOrCreateProfile($userId);

            // Get user information
            $user = $this->userRepository->findById($userId);

            // Clear old input if no errors
            if (! isset($_SESSION['errors'])) {
                clear_old_input();
            }

            view('student/profile', [
                'profile' => $profile,
                'user'    => $user,
            ], 'dashboard');
        } catch (Exception $e) {
            flash('error', 'Error loading profile: ' . $e->getMessage());
            redirect('/student/dashboard');
        }
    }

    /**
     * Update profile
     */
    public function update(): void
    {
        // Ensure user is authenticated and is a student
        if (! auth() || user_role() !== 'student') {
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
                redirect('/student/profile/edit');
                return;
            }

            // Update profile
            $this->profileService->updateProfile($userId, $_POST, $_FILES);

            flash('success', 'Profile updated successfully');
            redirect('/student/profile/edit');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/student/profile/edit');
        }
    }

    /**
     * Show public student profile
     */
    public function show(): void
    {
        // Get student ID from URL, or use current user if student
        $studentId = $_GET['id'] ?? null;

        // If no ID provided and user is a student, show their own profile
        if (! $studentId && auth() && user_role() === 'student') {
            $studentId = user_id();
        }

        if (! $studentId || ! is_numeric($studentId)) {
            flash('error', 'Invalid student ID');
            redirect('/');
            return;
        }

        try {
            // Get profile with user information
            $profile = $this->profileService->getProfileWithUser((int) $studentId);

            if (! $profile) {
                flash('error', 'Student profile not found');
                redirect('/');
                return;
            }

            // Check if user is a student
            if ($profile['role'] !== 'student') {
                flash('error', 'Profile not found');
                redirect('/');
                return;
            }

            // Get recent reviews
            require_once __DIR__ . '/../Services/ReviewService.php';
            require_once __DIR__ . '/../Repositories/ReviewRepository.php';
            require_once __DIR__ . '/../Repositories/OrderRepository.php';

            $reviewRepository = new ReviewRepository($this->db);
            $orderRepository  = new OrderRepository($this->db);
            $reviewService    = new ReviewService($reviewRepository, $orderRepository);

            // Get page number for pagination
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

            // Get reviews for this student
            $reviews      = $reviewService->getReviewsForStudent((int) $studentId, $page);
            $totalReviews = $reviewService->getReviewCount((int) $studentId);
            $totalPages   = ceil($totalReviews / 10);

            view('student/show', [
                'profile'      => $profile,
                'reviews'      => $reviews,
                'currentPage'  => $page,
                'totalPages'   => $totalPages,
                'totalReviews' => $totalReviews,
            ], 'base');
        } catch (Exception $e) {
            flash('error', 'Error loading profile: ' . $e->getMessage());
            redirect('/');
        }
    }

    /**
     * Delete portfolio file
     */
    public function deletePortfolioFile(): void
    {
        // Ensure user is authenticated and is a student
        if (! auth() || user_role() !== 'student') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId   = user_id();
        $filename = $_POST['filename'] ?? '';

        try {
            if (empty($filename)) {
                throw new Exception('Filename is required');
            }

            $this->profileService->deletePortfolioFile($userId, $filename);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(): void
    {
        // Ensure user is authenticated and is a student
        if (! auth() || user_role() !== 'student') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId = user_id();

        try {
            $this->profileService->deleteProfilePicture($userId);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Profile picture deleted successfully']);
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

        // Bio validation (optional, but if provided should not be too long)
        $bio = trim($data['bio'] ?? '');

        if (strlen($bio) > 2000) {
            $errors['bio'] = 'Bio must not exceed 2000 characters';
        }

        // Skills validation (optional)
        $skills = trim($data['skills'] ?? '');

        if (strlen($skills) > 500) {
            $errors['skills'] = 'Skills must not exceed 500 characters';
        }

        // Portfolio files validation
        if (! empty($files['portfolio_files']['name'][0])) {
            $fileCount = count($files['portfolio_files']['name']);

            // Check total number of files
            if ($fileCount > 10) {
                $errors['portfolio_files'] = 'Maximum 10 files allowed';
            }

            // Check individual file sizes
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['portfolio_files']['error'][$i] === UPLOAD_ERR_OK) {
                    $size = $files['portfolio_files']['size'][$i];

                    if ($size > 10 * 1024 * 1024) {
                        $errors['portfolio_files'] = 'Each file must not exceed 10MB';
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Connect Stripe account
     */
    public function connectStripe(): void
    {
        // Ensure user is authenticated and is a student
        if (! auth() || user_role() !== 'student') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Get or create profile
            $profile = $this->profileService->getOrCreateProfile($userId);

            // Check if already connected
            if (! empty($profile['stripe_onboarding_complete'])) {
                flash('info', 'Your Stripe account is already connected');
                redirect('/student/profile/edit');
                return;
            }

            // Get user information
            $user = $this->userRepository->findById($userId);

            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));

            // Create or retrieve Connect account
            if (empty($profile['stripe_connect_account_id'])) {
                // Create new Connect account
                $account = \Stripe\Account::create([
                    'type'    => 'express',
                    'email'   => $user['email'],
                    'country' => 'US', // Default to US, can be made configurable
                ]);

                // Save account ID
                $profileRepository = new StudentProfileRepository($this->db);
                $profileRepository->updateStripeConnect($userId, $account->id, false);

                $accountId = $account->id;
            } else {
                $accountId = $profile['stripe_connect_account_id'];
            }

            // Generate a state token for security
            $stateToken = bin2hex(random_bytes(32));

            // Store token in database (expires in 1 hour)
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);
            $stmt      = $this->db->prepare('
                INSERT INTO stripe_connect_tokens (token, user_id, stripe_account_id, expires_at)
                VALUES (:token, :user_id, :account_id, :expires_at)
            ');
            $stmt->execute([
                'token'      => $stateToken,
                'user_id'    => $userId,
                'account_id' => $accountId,
                'expires_at' => $expiresAt,
            ]);

            // Get base URL
            $baseUrl = $this->getBaseUrl();

            // Create account link for onboarding
            $accountLink = \Stripe\AccountLink::create([
                'account'     => $accountId,
                'refresh_url' => $baseUrl . '/student/profile/connect-stripe?state=' . $stateToken,
                'return_url'  => $baseUrl . '/student/profile/connect-stripe/return?state=' . $stateToken,
                'type'        => 'account_onboarding',
            ]);

            // Redirect to Stripe onboarding
            redirect($accountLink->url);
        } catch (Exception $e) {
            flash('error', 'Error connecting Stripe account: ' . $e->getMessage());
            redirect('/student/profile/edit');
        }
    }

    /**
     * Handle return from Stripe Connect onboarding
     */
    public function connectStripeReturn(): void
    {
        try {
            // Get state token from URL
            $stateToken = $_GET['state'] ?? '';

            if (empty($stateToken)) {
                flash('error', 'Invalid request. Please try connecting Stripe again.');
                redirect('/auth/login');
                return;
            }

            // Look up token in database
            $stmt = $this->db->prepare('
                SELECT user_id, stripe_account_id, expires_at
                FROM stripe_connect_tokens
                WHERE token = :token
                AND expires_at > NOW()
            ');
            $stmt->execute(['token' => $stateToken]);
            $tokenData = $stmt->fetch();

            if (! $tokenData) {
                flash('error', 'Invalid or expired token. Please try connecting Stripe again.');
                redirect('/auth/login');
                return;
            }

            $userId          = $tokenData['user_id'];
            $stripeAccountId = $tokenData['stripe_account_id'];

            // Delete the used token
            $stmt = $this->db->prepare('DELETE FROM stripe_connect_tokens WHERE token = :token');
            $stmt->execute(['token' => $stateToken]);

            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));

            // Retrieve account to check onboarding status
            $account = \Stripe\Account::retrieve($stripeAccountId);

            // Check if onboarding is complete
            if ($account->charges_enabled && $account->payouts_enabled) {
                // Update profile
                $profileRepository = new StudentProfileRepository($this->db);
                $profileRepository->updateStripeConnect($userId, $account->id, true);

                // Create a temporary session for the redirect
                $_SESSION['stripe_success_user_id'] = $userId;

                flash('success', 'Stripe account connected successfully! You can now receive payments.');
            } else {
                flash('warning', 'Stripe onboarding is not complete. Please complete all required steps.');
            }

            // Redirect to login with a special flag to redirect to profile
            redirect('/auth/login?stripe_return=1');
        } catch (Exception $e) {
            flash('error', 'Error verifying Stripe account: ' . $e->getMessage());
            redirect('/auth/login');
        }
    }
}
