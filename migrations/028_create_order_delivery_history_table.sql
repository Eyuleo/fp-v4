-- Create order_delivery_history table to track all delivery attempts
-- This allows complete history of deliveries with messages, files, and timestamps

CREATE TABLE order_delivery_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    delivery_message TEXT NULL,
    delivery_files JSON NULL,
    delivered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_number INT UNSIGNED NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_is_current (order_id, is_current),
    INDEX idx_delivered_at (delivered_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add current_delivery_id and delivery_count to orders table
ALTER TABLE orders
ADD COLUMN current_delivery_id INT UNSIGNED NULL AFTER delivery_files,
ADD COLUMN delivery_count INT UNSIGNED DEFAULT 0 AFTER current_delivery_id,
ADD FOREIGN KEY (current_delivery_id) REFERENCES order_delivery_history(id) ON DELETE SET NULL;

-- Add index for current_delivery_id
ALTER TABLE orders
ADD INDEX idx_current_delivery (current_delivery_id);
