ALTER TABLE disputes ADD COLUMN refund_percentage DECIMAL(5,2) NULL AFTER resolution;
ALTER TABLE disputes ADD COLUMN admin_notes TEXT NULL AFTER refund_percentage;
