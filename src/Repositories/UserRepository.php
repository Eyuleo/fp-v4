<?php

/**
 * User Repository
 *
 * Handles all database operations for users
 */
class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new user
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO users (name, email, password_hash, role, status, verification_token, created_at, updated_at)
            VALUES (:name, :email, :password_hash, :role, :status, :verification_token, NOW(), NOW())
        ');

        $stmt->execute([
            'name'               => $data['name'] ?? null,
            'email'              => $data['email'],
            'password_hash'      => $data['password_hash'],
            'role'               => $data['role'],
            'status'             => $data['status'] ?? 'unverified',
            'verification_token' => $data['verification_token'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find user by verification token
     */
    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE verification_token = ?');
        $stmt->execute([$token]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM users
            WHERE reset_token = :token
            AND reset_token_expires_at > :now
        ');
        
        $stmt->execute([
            'token' => $token,
            'now'   => date('Y-m-d H:i:s')
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Verify user email
     */
    public function verifyEmail(int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET status = :status,
                email_verified_at = NOW(),
                verification_token = NULL,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'status' => 'active',
            'id'     => $userId,
        ]);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET password_hash = :password_hash,
                reset_token = NULL,
                reset_token_expires_at = NULL,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'password_hash' => $passwordHash,
            'id'            => $userId,
        ]);
    }

    /**
     * Set password reset token
     */
    public function setResetToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET reset_token = :token,
                reset_token_expires_at = :expires_at,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'token'      => $token,
            'expires_at' => $expiresAt,
            'id'         => $userId,
        ]);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);

        return $stmt->fetchColumn() > 0;
    }

    /**
     * Update account information
     */
    public function updateAccount(int $userId, ?string $name, string $email): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET name = :name,
                email = :email,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'name'  => $name,
            'email' => $email,
            'id'    => $userId,
        ]);
    }

    /**
     * Get paginated users with filters
     */
    public function getPaginated(int $page, int $perPage, ?string $search, ?string $role, ?string $status): array
    {
        $offset = ($page - 1) * $perPage;

        $sql    = "SELECT * FROM users WHERE role != 'admin'";
        $params = [];

        // Apply search filter
        if ($search) {
            $sql .= " AND email LIKE :search";
            $params['search'] = '%' . $search . '%';
        }

        // Apply role filter
        if ($role) {
            if (in_array($role, ['student', 'client'])) {
                $sql .= " AND role = :role";
                $params['role'] = $role;
            } elseif ($role === 'admin') {
                // If someone tries to filter by admin, return no results
                $sql .= " AND 1=0";
            }
            // If role is invalid, ignore it and show all non-admin users
        }

        // Apply status filter
        if ($status && in_array($status, ['unverified', 'active', 'suspended'])) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        // Get total count
        $countSql  = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetch()['total'];

        // Add ordering and pagination
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        // Execute query
        $stmt = $this->db->prepare($sql);

        // Bind parameters with correct types
        foreach ($params as $key => $value) {
            if ($key === 'limit' || $key === 'offset') {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }

        $stmt->execute();
        $users = $stmt->fetchAll();

        return [
            'users'       => $users,
            'total'       => $totalCount,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalCount / $perPage),
        ];
    }

    /**
     * Update user status
     */
    public function updateStatus(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'status' => $status,
            'id'     => $userId,
        ]);
    }

    /**
     * Update user profile picture
     */
    public function updateProfilePicture(int $userId, ?string $profilePicture): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET profile_picture = :profile_picture,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'profile_picture' => $profilePicture,
            'id'              => $userId,
        ]);
    }

    /**
     * Update user name
     */
    public function updateName(int $userId, string $name): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET name = :name,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'name' => $name,
            'id'   => $userId,
        ]);
    }

    /**
     * Get all admin users
     */
    public function getAllAdmins(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE role = :role AND status = :status');
        $stmt->execute([
            'role'   => 'admin',
            'status' => 'active',
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Set suspension for a user
     * 
     * @param int $userId The user ID to suspend
     * @param int|null $days Number of days for suspension (null for permanent ban)
     * @return bool True if suspension was set successfully
     */
    public function setSuspension(int $userId, ?int $days): bool
    {
        $suspensionEndDate = null;
        
        if ($days !== null) {
            // Calculate suspension end date for temporary suspension
            $suspensionEndDate = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        }
        // If $days is null, suspension_end_date remains null (permanent ban)

        $stmt = $this->db->prepare('
            UPDATE users
            SET status = :status,
                suspension_end_date = :suspension_end_date,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'status'             => 'suspended',
            'suspension_end_date' => $suspensionEndDate,
            'id'                 => $userId,
        ]);
    }

    /**
     * Check if a user's suspension is currently active
     * 
     * @param int $userId The user ID to check
     * @return array ['is_suspended' => bool, 'suspension_end_date' => string|null]
     */
    public function checkSuspensionStatus(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT status, suspension_end_date
            FROM users
            WHERE id = :id
        ');
        
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'is_suspended'        => false,
                'suspension_end_date' => null,
            ];
        }

        // User is suspended if status is 'suspended' AND either:
        // 1. suspension_end_date is null (permanent ban), OR
        // 2. suspension_end_date is in the future
        $isSuspended = false;
        
        if ($user['status'] === 'suspended') {
            if ($user['suspension_end_date'] === null) {
                // Permanent ban
                $isSuspended = true;
            } else {
                // Temporary suspension - check if still active
                $isSuspended = strtotime($user['suspension_end_date']) > time();
            }
        }

        return [
            'is_suspended'        => $isSuspended,
            'suspension_end_date' => $user['suspension_end_date'],
        ];
    }

    /**
     * Clear suspension and restore user to active status
     * 
     * @param int $userId The user ID to restore
     * @return bool True if suspension was cleared successfully
     */
    public function clearSuspension(int $userId): bool
    {
        $stmt = $this->db->prepare('
            UPDATE users
            SET status = :status,
                suspension_end_date = NULL,
                updated_at = NOW()
            WHERE id = :id
        ');

        return $stmt->execute([
            'status' => 'active',
            'id'     => $userId,
        ]);
    }
}
