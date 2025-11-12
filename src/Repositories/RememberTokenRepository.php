<?php

/**
 * Remember Token Repository
 *
 * Handles all database operations for remember tokens
 */
class RememberTokenRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new remember token
     *
     * @param int $userId
     * @param string $tokenHash
     * @param string $expiresAt DateTime string in Y-m-d H:i:s format
     * @return int Token ID
     */
    public function create(int $userId, string $tokenHash, string $expiresAt): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at)
            VALUES (:user_id, :token_hash, :expires_at, NOW())
        ');

        $stmt->execute([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a valid remember token by token hash
     *
     * @param string $tokenHash
     * @return array|null Token data with user information
     */
    public function findValidToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare('
            SELECT rt.*, u.id as user_id, u.email, u.role, u.status
            FROM remember_tokens rt
            INNER JOIN users u ON rt.user_id = u.id
            WHERE rt.token_hash = :token_hash
            AND rt.expires_at > NOW()
            AND u.status = "active"
        ');

        $stmt->execute(['token_hash' => $tokenHash]);

        $token = $stmt->fetch();

        return $token ?: null;
    }

    /**
     * Delete a specific remember token
     *
     * @param string $tokenHash
     * @return bool
     */
    public function deleteByTokenHash(string $tokenHash): bool
    {
        $stmt = $this->db->prepare('
            DELETE FROM remember_tokens
            WHERE token_hash = :token_hash
        ');

        return $stmt->execute(['token_hash' => $tokenHash]);
    }

    /**
     * Delete all remember tokens for a user
     *
     * @param int $userId
     * @return bool
     */
    public function deleteByUserId(int $userId): bool
    {
        $stmt = $this->db->prepare('
            DELETE FROM remember_tokens
            WHERE user_id = :user_id
        ');

        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Delete expired tokens (cleanup)
     *
     * @return int Number of deleted tokens
     */
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare('
            DELETE FROM remember_tokens
            WHERE expires_at <= NOW()
        ');

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Validate if a token exists and is not expired
     *
     * @param string $tokenHash
     * @return bool
     */
    public function isValid(string $tokenHash): bool
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count
            FROM remember_tokens
            WHERE token_hash = :token_hash
            AND expires_at > NOW()
        ');

        $stmt->execute(['token_hash' => $tokenHash]);

        $result = $stmt->fetch();

        return $result && $result['count'] > 0;
    }
}
