-- Create service_moderation_log table
-- Note: Service moderation fields (rejection_reason, rejected_at, rejected_by, status enum)
-- were already added in migration 024_add_service_edit_tracking.sql
CREATE TABLE service_moderation_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    action ENUM('approve', 'reject', 'resubmit') NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_service_id (service_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
