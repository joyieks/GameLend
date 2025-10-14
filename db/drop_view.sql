-- Drop the v_game_statistics view
-- This is safe to run - views don't contain actual data

DROP VIEW IF EXISTS v_game_statistics CASCADE;

-- Verify it's gone
SELECT table_name, table_type 
FROM information_schema.tables 
WHERE table_schema = 'public' 
AND table_name LIKE 'v_%';

-- Show all remaining views
SELECT table_name 
FROM information_schema.views 
WHERE table_schema = 'public';
