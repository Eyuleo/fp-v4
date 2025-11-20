ALTER TABLE users ADD COLUMN suspension_end_date TIMESTAMP NULL AFTER status;
ALTER TABLE users ADD INDEX idx_suspension (status, suspension_end_date);
