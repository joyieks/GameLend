-- Add quantity columns to games table
ALTER TABLE `games` 
ADD COLUMN `total_quantity` int(11) NOT NULL DEFAULT 1 AFTER `platform`,
ADD COLUMN `available_quantity` int(11) NOT NULL DEFAULT 1 AFTER `total_quantity`;

-- Update existing games to have default quantities
UPDATE `games` SET `total_quantity` = 1, `available_quantity` = CASE WHEN `status` = 'borrowed' THEN 0 ELSE 1 END;