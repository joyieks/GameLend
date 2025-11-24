-- Add image_url column to games table if it doesn't exist
ALTER TABLE games ADD COLUMN IF NOT EXISTS image_url TEXT;

-- Add comment
COMMENT ON COLUMN games.image_url IS 'URL to the game cover image stored in Supabase storage';

