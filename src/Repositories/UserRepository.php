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
            INSERT INTO users (email, password_hash, role, status, verification_token, created_at, updated_at)
            VALUES (:email, :password_hash, :role, :status, :verification_token, NOW(), NOW())
        ');

        $stmt->execute([
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
            WHERE reset_token = ?
            AND reset_token_expires_at > NOW()
        ');
        $stmt->execute([$token]);

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
}
