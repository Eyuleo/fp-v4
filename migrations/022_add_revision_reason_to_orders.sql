-- Add revision_reason column to orders table
-- This stores the client's reason for requesting a revision

ALTER TABLE orders
ADD COLUMN revision_reason TEXT NULL AFTER revision_count;
