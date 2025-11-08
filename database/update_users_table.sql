USE accountmanager;

-- Add name column to users table
ALTER TABLE users
ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT 'User';

-- Update existing users with default names
UPDATE users SET full_name = 'Administrator' WHERE username = 'admin';
UPDATE users SET full_name = 'Teacher One' WHERE username = 'teacher1';
UPDATE users SET full_name = 'Student One' WHERE username = 'student1';