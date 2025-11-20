<?php

require_once __DIR__ . '/../Repositories/ViolationRepository.php';
require_once __DIR__ . '/../Repositories/UserRepository.php';
require_once __DIR__ . '/../Repositories/MessageRepository.php';
require_once __DIR__ . '/NotificationService.php';

/**
 * Violation Service
 *
 * Business logic for managing user violations and penalties
 */
class ViolationService
{
    private ViolationRepository $violationRepository;
    private UserRepository $userRepository;
    private MessageRepository $messageRepository;
    private NotificationService $notificationService;
    private PDO $db;

    public function __construct(
        ViolationRepository $violationRepository,
        UserRepository $userRepository,
        MessageRepository $messageRepository,
        NotificationService $notificationService,
        PDO $db
    ) {
        $this->violationRepository = $violationRepository;
        $this->userRepository = $userRepository;
        $this->messageRepository = $messageRepository;
        $this->notificationService = $notificationService;
        $this->db = $db;
    }

    /**
     * Confirm a message as a violation and apply penalty
     *
     * @param int $messageId The message ID to confirm as violation
     * @param int $adminId The admin confirming the violation
     * @param array $data Violation data including violation_type, severity, penalty_type, suspension_days, admin_notes
     * @return array ['success' => bool, 'violation_id' => int|null, 'errors' => array]
     */
    public function confirmViolation(int $messageId, int $adminId, array $data): array
    {
        // Validate required fields
        $errors = [];
        
        if (empty($data['violation_type'])) {
            $errors['violation_type'] = 'Violation type is required';
        }
        
        if (empty($data['severity'])) {
            $errors['severity'] = 'Severity is required';
        }
        
        if (empty($data['penalty_type'])) {
            $errors['penalty_type'] = 'Penalty type is required';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'violation_id' => null,
                'errors' => $errors,
            ];
        }

        // Get the message to find the sender
        $sql = "SELECT * FROM messages WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            return [
                'success' => false,
                'violation_id' => null,
                'errors' => ['message' => 'Message not found'],
            ];
        }

        $userId = $message['sender_id'];

        try {
            // Create violation record
            $violationData = [
                'user_id' => $userId,
                'message_id' => $messageId,
                'violation_type' => $data['violation_type'],
                'severity' => $data['severity'],
                'penalty_type' => $data['penalty_type'],
                'suspension_days' => $data['suspension_days'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
                'confirmed_by' => $adminId,
            ];

            $violationId = $this->violationRepository->create($violationData);

            // Apply suspension if penalty requires it
            if ($data['penalty_type'] === 'temp_suspension') {
                $days = $data['suspension_days'] ?? 7; // Default to 7 days if not specified
                $this->applySuspension($userId, $days);
            } elseif ($data['penalty_type'] === 'permanent_ban') {
                $this->applySuspension($userId, null); // null means permanent
            }
            // For 'warning' penalty, no suspension is applied

            // Log to audit_logs table
            $this->logAuditAction($adminId, 'violation_confirmed', [
                'violation_id' => $violationId,
                'message_id' => $messageId,
                'user_id' => $userId,
                'penalty_type' => $data['penalty_type'],
            ]);

            // Send notification to user
            $user = $this->userRepository->findById($userId);
            if ($user) {
                $this->sendViolationNotification($user, $violationData);
            }

            return [
                'success' => true,
                'violation_id' => $violationId,
                'errors' => [],
            ];
        } catch (Exception $e) {
            error_log('Violation confirmation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'violation_id' => null,
                'errors' => ['database' => 'Failed to confirm violation'],
            ];
        }
    }

    /**
     * Dismiss a flagged message
     *
     * @param int $messageId The message ID to dismiss
     * @param int $adminId The admin dismissing the flag
     * @return array ['success' => bool, 'errors' => array]
     */
    public function dismissFlag(int $messageId, int $adminId): array
    {
        try {
            // Update message to set is_flagged to false
            $sql = "UPDATE messages SET is_flagged = FALSE WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute(['id' => $messageId]);

            if (!$success) {
                return [
                    'success' => false,
                    'errors' => ['database' => 'Failed to dismiss flag'],
                ];
            }

            // Log to audit_logs table
            $this->logAuditAction($adminId, 'flag_dismissed', [
                'message_id' => $messageId,
            ]);

            return [
                'success' => true,
                'errors' => [],
            ];
        } catch (Exception $e) {
            error_log('Flag dismissal failed: ' . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['database' => 'Failed to dismiss flag'],
            ];
        }
    }

    /**
     * Get all violations for a user
     *
     * @param int $userId The user ID
     * @return array Array of violation records
     */
    public function getUserViolations(int $userId): array
    {
        return $this->violationRepository->findByUserId($userId);
    }

    /**
     * Calculate suggested penalty based on user's violation history
     *
     * @param int $userId The user ID
     * @return string Suggested penalty type: 'warning', 'temp_suspension', or 'permanent_ban'
     */
    public function calculateSuggestedPenalty(int $userId): string
    {
        $violationCount = $this->violationRepository->countByUserId($userId);

        if ($violationCount === 0) {
            return 'warning';
        } elseif ($violationCount === 1) {
            return 'temp_suspension';
        } else {
            // 2 or more violations
            return 'permanent_ban';
        }
    }

    /**
     * Apply suspension to a user
     *
     * @param int $userId The user ID to suspend
     * @param int|null $days Number of days for suspension (null for permanent ban)
     * @return bool True if suspension was applied successfully
     */
    public function applySuspension(int $userId, ?int $days): bool
    {
        return $this->userRepository->setSuspension($userId, $days);
    }

    /**
     * Check if a user's suspension is currently active
     *
     * @param int $userId The user ID to check
     * @return array ['is_suspended' => bool, 'suspension_end_date' => string|null]
     */
    public function checkSuspensionStatus(int $userId): array
    {
        return $this->userRepository->checkSuspensionStatus($userId);
    }

    /**
     * Send violation notification to user
     *
     * @param array $user User data
     * @param array $violationData Violation data
     * @return void
     */
    public function sendViolationNotification(array $user, array $violationData): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';
        
        // Determine penalty description
        $penaltyDescription = '';
        $duration = '';
        
        switch ($violationData['penalty_type']) {
            case 'warning':
                $penaltyDescription = 'Warning';
                $duration = 'This is a warning. Future violations may result in suspension.';
                break;
            case 'temp_suspension':
                $penaltyDescription = 'Temporary Suspension';
                $days = $violationData['suspension_days'] ?? 7;
                $duration = "Your account has been suspended for {$days} days.";
                break;
            case 'permanent_ban':
                $penaltyDescription = 'Permanent Ban';
                $duration = 'Your account has been permanently banned.';
                break;
        }

        $this->notificationService->notify(
            $user['id'],
            $user['email'],
            'violation_confirmed',
            'Policy Violation - Action Taken',
            "A policy violation has been confirmed on your account",
            'emails/violation-notification',
            [
                'user_name' => $user['name'] ?? $user['email'],
                'violation_type' => ucfirst(str_replace('_', ' ', $violationData['violation_type'])),
                'severity' => ucfirst($violationData['severity']),
                'penalty_type' => $penaltyDescription,
                'duration' => $duration,
                'admin_notes' => $violationData['admin_notes'] ?? '',
                'appeal_url' => $appUrl . '/settings/account',
            ],
            $appUrl . '/settings/account'
        );
    }

    /**
     * Send notification when a message is flagged
     *
     * @param array $user User data
     * @param int $messageId The flagged message ID
     * @return void
     */
    public function sendFlaggedMessageNotification(array $user, int $messageId): void
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:8000';

        $this->notificationService->createInAppNotification(
            $user['id'],
            'message_flagged',
            'Message Under Review',
            'One of your messages has been flagged for review by our moderation team',
            $appUrl . '/messages'
        );
    }

    /**
     * Log an audit action
     *
     * @param int $adminId The admin performing the action
     * @param string $action The action type
     * @param array $details Additional details
     * @return void
     */
    private function logAuditAction(int $adminId, string $action, array $details): void
    {
        try {
            $sql = "INSERT INTO audit_logs (user_id, action, details, created_at)
                    VALUES (:user_id, :action, :details, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $adminId,
                'action' => $action,
                'details' => json_encode($details),
            ]);
        } catch (Exception $e) {
            error_log('Audit log failed: ' . $e->getMessage());
            // Don't throw - audit logging failure shouldn't break the main operation
        }
    }
}
