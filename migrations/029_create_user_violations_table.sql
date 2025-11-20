CREATE TABLE user_violations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message_id INT UNSIGNED NULL,
    violation_type ENUM('off_platform_contact', 'payment_circumvention', 'other') NOT NULL,
    severity ENUM('warning', 'minor', 'major', 'critical') NOT NULL,
    penalty_type ENUM('warning', 'temp_suspension', 'permanent_ban') NOT NULL,
    suspension_days INT NULL,
    admin_notes TEXT NULL,
    confirmed_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL,
    FOREIGN KEY (confirmed_by) REFERENCES users(id),
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
