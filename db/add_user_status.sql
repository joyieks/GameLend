-- Add status field to users table
-- This script adds a status field to enable/disable users

ALTER TABLE `users` 
ADD COLUMN `status` ENUM('active', 'disabled') DEFAULT 'active' AFTER `role`;

-- Update existing users to be active
UPDATE `users` SET `status` = 'active' WHERE `status` IS NULL;

