-- Create order_revision_history table to track all revision requests
-- This allows complete history of revision requests with timestamps and reasons

CREATE TABLE order_revision_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    revision_reason TEXT NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revision_number INT UNSIGNED NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_order_id (order_id),
    INDEX idx_is_current (order_id, is_current),
    INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add current_revision_id and revision_history_count to orders table
ALTER TABLE orders
ADD COLUMN current_revision_id INT UNSIGNED NULL AFTER revision_reason,
ADD COLUMN revision_history_count INT UNSIGNED DEFAULT 0 AFTER current_revision_id,
ADD FOREIGN KEY (current_revision_id) REFERENCES order_revision_history(id) ON DELETE SET NULL;

-- Add index for current_revision_id
ALTER TABLE orders
ADD INDEX idx_current_revision (current_revision_id);
