-- Add review window fields to orders and backfill existing delivered rows.
-- Run these statements directly against your database.

-- 1) Schema changes
ALTER TABLE orders
    ADD COLUMN delivered_at TIMESTAMP NULL AFTER delivery_files,
    ADD COLUMN review_deadline TIMESTAMP NULL AFTER delivered_at,
    ADD COLUMN review_window_hours INT UNSIGNED NULL AFTER review_deadline,
    ADD COLUMN auto_completed_at TIMESTAMP NULL AFTER completed_at;

-- Helpful index for auto-completion lookups: delivered orders whose review window expired
CREATE INDEX idx_status_review_deadline ON orders (status, review_deadline);

-- 2) Backfill for existing delivered orders (adjust @default_review_hours if desired)
SET @default_review_hours = 24;

-- If delivered_at is missing for delivered rows, use updated_at, falling back to created_at
UPDATE orders
SET delivered_at = COALESCE(delivered_at, updated_at, created_at)
WHERE status = 'delivered' AND delivered_at IS NULL;

-- Ensure review_window_hours has a value for delivered rows
UPDATE orders
SET review_window_hours = COALESCE(review_window_hours, @default_review_hours)
WHERE status = 'delivered';

-- Initialize review_deadline for delivered rows that are missing it
UPDATE orders
SET review_deadline = DATE_ADD(delivered_at, INTERVAL review_window_hours HOUR)
WHERE status = 'delivered'
  AND delivered_at IS NOT NULL
  AND review_deadline IS NULL;
