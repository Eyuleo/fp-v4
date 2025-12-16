-- Add performance indexes for frequently queried columns

-- Orders table indexes for better query performance
ALTER TABLE orders ADD INDEX idx_status_created (status, created_at);
ALTER TABLE orders ADD INDEX idx_status_completed (status, completed_at);
ALTER TABLE orders ADD INDEX idx_student_status (student_id, status);
ALTER TABLE orders ADD INDEX idx_client_status (client_id, status);
ALTER TABLE orders ADD INDEX idx_service_status (service_id, status);
ALTER TABLE orders ADD INDEX idx_deadline (deadline);

-- Payments table indexes
ALTER TABLE payments ADD INDEX idx_status_created (status, created_at);
ALTER TABLE payments ADD INDEX idx_order_id (order_id);

-- Messages table indexes
ALTER TABLE messages ADD INDEX idx_order_sender (order_id, sender_id);
ALTER TABLE messages ADD INDEX idx_read_flags (read_by_client, read_by_student);

-- Services table additional indexes
ALTER TABLE services ADD INDEX idx_status_created (status, created_at);
ALTER TABLE services ADD INDEX idx_delivery_days (delivery_days);

-- Student profiles indexes
ALTER TABLE student_profiles ADD INDEX idx_average_rating (average_rating);
ALTER TABLE student_profiles ADD INDEX idx_user_id (user_id);

-- Notifications table indexes
ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read);
ALTER TABLE notifications ADD INDEX idx_user_created (user_id, created_at);

-- Reviews table indexes
ALTER TABLE reviews ADD INDEX idx_student_hidden (student_id, is_hidden);
ALTER TABLE reviews ADD INDEX idx_order_hidden (order_id, is_hidden);
