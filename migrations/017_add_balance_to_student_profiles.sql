-- Add balance fields to student_profiles table
ALTER TABLE student_profiles 
ADD COLUMN available_balance DECIMAL(10,2) DEFAULT 0.00 AFTER total_orders,
ADD COLUMN pending_balance DECIMAL(10,2) DEFAULT 0.00 AFTER available_balance,
ADD COLUMN total_withdrawn DECIMAL(10,2) DEFAULT 0.00 AFTER pending_balance;
