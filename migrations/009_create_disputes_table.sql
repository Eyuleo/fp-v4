CREATE TABLE disputes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED UNIQUE NOT NULL,
    opened_by INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('open', 'resolved') DEFAULT 'open',
    resolution ENUM('release_to_student', 'refund_to_client', 'partial_refund') NULL,
    resolution_notes TEXT NULL,
    resolved_by INT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (opened_by) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
