-- Add name column to users table
ALTER TABLE users 
ADD COLUMN name VARCHAR(255) NULL AFTER id;
