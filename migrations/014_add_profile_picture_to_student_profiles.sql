-- Add profile_picture column to student_profiles table
ALTER TABLE student_profiles 
ADD COLUMN profile_picture VARCHAR(255) NULL AFTER portfolio_files;
