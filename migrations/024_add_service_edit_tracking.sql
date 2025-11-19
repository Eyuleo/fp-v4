-- Add service edit tracking fields and create service_edit_history table

-- Add fields to services table for edit tracking and rejection
ALTER TABLE services
ADD COLUMN edit_locked BOOLEAN DEFAULT FALSE COMMENT 'Prevents editing when service has active orders',
ADD COLUMN rejection_reason TEXT NULL COMMENT 'Reason for service rejection by admin',
ADD COLUMN rejected_at TIMESTAMP NULL COMMENT 'When the service was rejected',
ADD COLUMN rejected_by INT UNSIGNED NULL COMMENT 'Admin user ID who rejected the service',
ADD COLUMN last_modified_at TIMESTAMP NULL COMMENT 'Last time service was edited',
ADD CONSTRAINT fk_services_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update status enum to include 'pending' and 'rejected' statuses
ALTER TABLE services
MODIFY COLUMN status ENUM('inactive', 'active', 'paused', 'pending', 'rejected') DEFAULT 'inactive';

-- Create service_edit_history table for audit trail
CREATE TABLE service_edit_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    field_changed VARCHAR(100) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    has_active_orders BOOLEAN DEFAULT FALSE COMMENT 'Snapshot of whether service had active orders at time of edit',
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_service_changed (service_id, changed_at),
    INDEX idx_user_changed (user_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
