-- Fix deadline column type to avoid automatic updates on row modification
-- Changing from TIMESTAMP to DATETIME prevents "ON UPDATE CURRENT_TIMESTAMP" behavior

ALTER TABLE orders MODIFY COLUMN deadline DATETIME NOT NULL;
