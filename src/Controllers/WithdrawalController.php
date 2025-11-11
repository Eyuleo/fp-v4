<?php

/**
 * Withdrawal Controller
 *
 * Handles student withdrawal requests
 */
class WithdrawalController
{
    private WithdrawalService $withdrawalService;
    private PDO $db;

    public function __construct()
    {
        $this->db                = $this->getDatabase();
        $this->withdrawalService = new WithdrawalService($this->db);
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
     * Show withdrawal page
     */
    public function index(): void
    {
        if (! auth() || user_role() !== 'student') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Get balance
            $balance = $this->withdrawalService->getBalance($userId);

            // Get withdrawal history
            $withdrawals = $this->withdrawalService->getWithdrawalHistory($userId);

            // Get Stripe Connect status
            $stmt = $this->db->prepare('
                SELECT stripe_connect_account_id, stripe_onboarding_complete
                FROM student_profiles
                WHERE user_id = :user_id
            ');
            $stmt->execute(['user_id' => $userId]);
            $stripeStatus = $stmt->fetch();

            // Clear old input if no errors
            if (! isset($_SESSION['errors'])) {
                clear_old_input();
            }

            view('student/withdrawals/index', [
                'balance'      => $balance,
                'withdrawals'  => $withdrawals,
                'stripeStatus' => $stripeStatus,
            ], 'dashboard');
        } catch (Exception $e) {
            flash('error', 'Error loading withdrawals: ' . $e->getMessage());
            redirect('/student/dashboard');
        }
    }

    /**
     * Get Stripe Express Dashboard login link
     */
    public function stripeDashboard(): void
    {
        if (! auth() || user_role() !== 'student') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            // Get Stripe Connect account ID
            $stmt = $this->db->prepare('
                SELECT stripe_connect_account_id, stripe_onboarding_complete
                FROM student_profiles
                WHERE user_id = :user_id
            ');
            $stmt->execute(['user_id' => $userId]);
            $profile = $stmt->fetch();

            if (! $profile || ! $profile['stripe_onboarding_complete']) {
                flash('error', 'Please complete Stripe Connect onboarding first');
                redirect('/student/profile/edit');
                return;
            }

            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));

            // Create login link for Express Dashboard
            $loginLink = \Stripe\Account::createLoginLink($profile['stripe_connect_account_id']);

            // Redirect to Stripe Express Dashboard
            redirect($loginLink->url);
        } catch (Exception $e) {
            flash('error', 'Error accessing Stripe dashboard: ' . $e->getMessage());
            redirect('/student/withdrawals');
        }
    }

    /**
     * Request withdrawal
     */
    public function request(): void
    {
        if (! auth() || user_role() !== 'student') {
            redirect('/auth/login');
            return;
        }

        $userId = user_id();

        try {
            $amount = floatval($_POST['amount'] ?? 0);

            $errors = [];

            if ($amount <= 0) {
                $errors['amount'] = 'Please enter a valid amount';
            }

            if (! empty($errors)) {
                $_SESSION['errors'] = $errors;
                flash_input($_POST);
                redirect('/student/withdrawals');
                return;
            }

            // Request withdrawal
            $withdrawal = $this->withdrawalService->requestWithdrawal($userId, $amount);

            flash('success', 'Withdrawal request submitted successfully. Funds will be transferred to your Stripe account.');
            redirect('/student/withdrawals');
        } catch (Exception $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            flash_input($_POST);
            redirect('/student/withdrawals');
        }
    }
}
