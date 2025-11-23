<?php

/**
 * Student Profile Repository
 *
 * Handles all database operations for student profiles
 */
class StudentProfileRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find profile by user ID
     */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);

        $profile = $stmt->fetch();

        return $profile ?: null;
    }

    /**
     * Create a new student profile
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO student_profiles (
                user_id, bio, skills, portfolio_files,
                created_at, updated_at
            )
            VALUES (
                :user_id, :bio, :skills, :portfolio_files,
                NOW(), NOW()
            )
        ');

        $stmt->execute([
            'user_id'         => $data['user_id'],
            'bio'             => $data['bio'] ?? null,
            'skills'          => $data['skills'] ?? json_encode([]),
            'portfolio_files' => $data['portfolio_files'] ?? json_encode([]),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update student profile
     */
    public function update(int $userId, array $data): bool
    {
        $fields = [
            'bio = :bio',
            'skills = :skills',
            'portfolio_files = :portfolio_files',
            'updated_at = NOW()',
        ];

        $params = [
            'bio'             => $data['bio'] ?? null,
            'skills'          => $data['skills'] ?? json_encode([]),
            'portfolio_files' => $data['portfolio_files'] ?? json_encode([]),
            'user_id'         => $userId,
        ];

        // Add profile picture if provided
        if (isset($data['profile_picture'])) {
            $fields[]                  = 'profile_picture = :profile_picture';
            $params['profile_picture'] = $data['profile_picture'];
        }

        $sql  = 'UPDATE student_profiles SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Update Stripe Connect information
     */
    public function updateStripeConnect(int $userId, string $accountId, bool $onboardingComplete = false): bool
    {
        $stmt = $this->db->prepare('
            UPDATE student_profiles
            SET stripe_connect_account_id = :account_id,
                stripe_onboarding_complete = :onboarding_complete,
                updated_at = NOW()
            WHERE user_id = :user_id
        ');

        return $stmt->execute([
            'account_id'          => $accountId,
            'onboarding_complete' => $onboardingComplete ? 1 : 0,
            'user_id'             => $userId,
        ]);
    }

    /**
     * Update student statistics
     */
    public function updateStatistics(int $userId, array $data): bool
    {
        $fields = [];
        $params = ['user_id' => $userId];

        if (isset($data['average_rating'])) {
            $fields[]                 = 'average_rating = :average_rating';
            $params['average_rating'] = $data['average_rating'];
        }

        if (isset($data['total_reviews'])) {
            $fields[]                = 'total_reviews = :total_reviews';
            $params['total_reviews'] = $data['total_reviews'];
        }

        if (isset($data['total_orders'])) {
            $fields[]               = 'total_orders = :total_orders';
            $params['total_orders'] = $data['total_orders'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = NOW()';

        $sql  = 'UPDATE student_profiles SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Get profile with user information
     */
    public function getProfileWithUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT
                sp.*,
                u.name,
                u.email,
                u.role,
                u.status,
                u.created_at as user_created_at
            FROM student_profiles sp
            INNER JOIN users u ON sp.user_id = u.id
            WHERE sp.user_id = ?
        ');
        $stmt->execute([$userId]);

        $profile = $stmt->fetch();

        return $profile ?: null;
    }
}
